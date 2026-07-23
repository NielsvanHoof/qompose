<?php

declare(strict_types=1);

namespace App\Services\Ocr\Normalization;

use App\Contracts\Ocr\StructuresDocumentText;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Illuminate\Contracts\Config\Repository;
use JsonException;
use RuntimeException;

use function is_array;
use function is_string;
use function json_decode;

/**
 * Asks Bedrock to group OCR line text into our extraction schema.
 * Supports Claude and OpenAI gpt-oss (reasoning + text content blocks).
 *
 * @phpstan-import-type DocumentExtractionField from StructuresDocumentText
 * @phpstan-import-type DocumentExtractionTable from StructuresDocumentText
 * @phpstan-import-type DocumentExtractionPayload from StructuresDocumentText
 */
final class BedrockDocumentStructureNormalizer implements StructuresDocumentText
{
    public function __construct(
        private readonly BedrockRuntimeClient $bedrock,
        private readonly Repository $config,
    ) {}

    /**
     * @return DocumentExtractionPayload
     */
    public function structure(string $plainText): array
    {
        $trimmed = mb_trim($plainText);

        if ($trimmed === '') {
            return $this->emptyPayload();
        }

        $modelId = $this->config->get('ocr.bedrock.model_id');
        $maxTokens = (int) $this->config->get('ocr.bedrock.max_tokens', 4096);
        $temperature = (float) $this->config->get('ocr.bedrock.temperature', 0);

        if (! is_string($modelId) || $modelId === '') {
            throw new RuntimeException('OCR Bedrock model id is not configured.');
        }

        $converseArgs = [
            'modelId' => $modelId,
            'system' => [
                ['text' => $this->systemPrompt()],
            ],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['text' => $this->userPrompt($trimmed)],
                    ],
                ],
            ],
            'inferenceConfig' => [
                // GPT-OSS spends tokens on reasoning first — keep headroom for JSON.
                'maxTokens' => max(1024, $maxTokens),
                'temperature' => max(0.0, min(1.0, $temperature)),
            ],
        ];

        // OpenAI gpt-oss models return reasoningContent + text; keep reasoning light.
        if ($this->usesOpenAiGptOss($modelId)) {
            $converseArgs['additionalModelRequestFields'] = [
                'reasoning_effort' => 'low',
            ];
        }

        $result = $this->bedrock->converse($converseArgs);

        $text = $this->extractAssistantText($result->toArray());

        return $this->decodeAndNormalize($text);
    }

    private function usesOpenAiGptOss(string $modelId): bool
    {
        return str_contains($modelId, 'gpt-oss') || str_contains($modelId, 'openai.');
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You structure OCR text from Dutch and international financial/identity documents
(payslips, bank statements, invoices, ID cards, tax forms).

Return ONLY valid JSON matching this schema (no markdown fences, no commentary):
{
  "document_type": string|null,
  "summary": string|null,
  "fields": [{"label": string, "value": string|string[]}],
  "tables": [{"title": string|null, "headers": string[], "rows": string[][]}],
  "notes": string[]
}

Rules:
- document_type: short snake_case label when clear (payslip, bank_statement, invoice, identity, tax_form, other), else null.
- summary: one short sentence describing the document, or null.
- fields: label/value pairs. Prefer human labels from the document. Duplicate labels become a string array value.
- tables: reconstruct tabular data; use headers when obvious, otherwise empty headers and put all rows in rows.
- notes: short caveats (illegible areas, contradictions). Empty array when none.
- Do not invent values that are not present in the OCR text.
- Keep original language for labels and values.
PROMPT;
    }

    private function userPrompt(string $plainText): string
    {
        return "OCR text to structure:\n\n{$plainText}";
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function extractAssistantText(array $result): string
    {
        $output = $result['output'] ?? null;

        if (! is_array($output)) {
            throw new RuntimeException('Bedrock Converse response is missing output.');
        }

        $message = $output['message'] ?? null;

        if (! is_array($message)) {
            throw new RuntimeException('Bedrock Converse response is missing message.');
        }

        $content = $message['content'] ?? null;

        if (! is_array($content)) {
            throw new RuntimeException('Bedrock Converse response is missing content.');
        }

        // Prefer final answer text blocks. GPT-OSS also emits reasoningContent blocks.
        $answerParts = [];
        $reasoningParts = [];

        foreach ($content as $block) {
            if (! is_array($block)) {
                continue;
            }

            $text = $block['text'] ?? null;

            if (is_string($text) && $text !== '') {
                $answerParts[] = $text;

                continue;
            }

            $reasoning = $this->extractReasoningText($block);

            if ($reasoning !== null) {
                $reasoningParts[] = $reasoning;
            }
        }

        $joined = mb_trim(implode("\n", $answerParts));

        if ($joined !== '') {
            return $joined;
        }

        // Fallback: some SDK shapes only expose reasoning text when maxTokens was too small.
        $joinedReasoning = mb_trim(implode("\n", $reasoningParts));

        if ($joinedReasoning !== '') {
            throw new RuntimeException(
                'Bedrock returned reasoning but no answer text. Increase OCR_BEDROCK_MAX_TOKENS or lower reasoning effort. stopReason='.
                (is_string($result['stopReason'] ?? null) ? $result['stopReason'] : 'unknown'),
            );
        }

        $blockKeys = [];

        foreach ($content as $block) {
            if (is_array($block)) {
                $blockKeys[] = implode(',', array_keys($block));
            }
        }

        throw new RuntimeException(
            'Bedrock returned an empty response. stopReason='.
            (is_string($result['stopReason'] ?? null) ? $result['stopReason'] : 'unknown').
            ' contentKeys=['.implode('|', $blockKeys).']',
        );
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function extractReasoningText(array $block): ?string
    {
        $reasoningContent = $block['reasoningContent'] ?? null;

        if (! is_array($reasoningContent)) {
            // AWS SDK may wrap unknown unions as ['$unknown' => ['reasoning_content', [...]]]
            $unknown = $block['$unknown'] ?? null;

            if (is_array($unknown) && ($unknown[0] ?? null) === 'reasoning_content' && is_array($unknown[1] ?? null)) {
                $reasoningContent = $unknown[1];
            } else {
                return null;
            }
        }

        $reasoningText = $reasoningContent['reasoningText'] ?? null;

        if (is_array($reasoningText)) {
            $text = $reasoningText['text'] ?? null;

            return is_string($text) && $text !== '' ? $text : null;
        }

        if (is_string($reasoningText) && $reasoningText !== '') {
            return $reasoningText;
        }

        $text = $reasoningContent['text'] ?? null;

        return is_string($text) && $text !== '' ? $text : null;
    }

    /**
     * @return DocumentExtractionPayload
     */
    private function decodeAndNormalize(string $text): array
    {
        $json = $this->unwrapJson($text);

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Bedrock returned invalid JSON.', 0, $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Bedrock JSON root must be an object.');
        }

        return [
            'document_type' => $this->nullableString($decoded['document_type'] ?? null),
            'summary' => $this->nullableString($decoded['summary'] ?? null),
            'fields' => $this->normalizeFields($decoded['fields'] ?? []),
            'tables' => $this->normalizeTables($decoded['tables'] ?? []),
            'notes' => $this->normalizeStringList($decoded['notes'] ?? []),
        ];
    }

    /**
     * Models sometimes wrap JSON in ```json fences despite instructions.
     */
    private function unwrapJson(string $text): string
    {
        $trimmed = mb_trim($text);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $trimmed, $matches) === 1) {
            return mb_trim($matches[1]);
        }

        return $trimmed;
    }

    /**
     * @return list<DocumentExtractionField>
     */
    private function normalizeFields(mixed $fields): array
    {
        if (! is_array($fields)) {
            return [];
        }

        $normalized = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $label = $this->nullableString($field['label'] ?? null);

            if ($label === null || $label === '') {
                continue;
            }

            $value = $field['value'] ?? '';

            if (is_array($value)) {
                $values = $this->normalizeStringList($value);
                $normalized[] = [
                    'label' => $label,
                    'value' => $values === [] ? '' : $values,
                ];

                continue;
            }

            $normalized[] = [
                'label' => $label,
                'value' => is_string($value) ? mb_trim($value) : (string) $value,
            ];
        }

        return $normalized;
    }

    /**
     * @return list<DocumentExtractionTable>
     */
    private function normalizeTables(mixed $tables): array
    {
        if (! is_array($tables)) {
            return [];
        }

        $normalized = [];

        foreach ($tables as $table) {
            if (! is_array($table)) {
                continue;
            }

            $headers = $this->normalizeStringList($table['headers'] ?? []);
            $rows = [];

            $rawRows = $table['rows'] ?? [];

            if (is_array($rawRows)) {
                foreach ($rawRows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $cells = [];

                    foreach ($row as $cell) {
                        $cells[] = is_string($cell) ? mb_trim($cell) : (string) $cell;
                    }

                    $rows[] = $cells;
                }
            }

            // Skip empty tables — no headers and no rows.
            if ($headers === [] && $rows === []) {
                continue;
            }

            $normalized[] = [
                'title' => $this->nullableString($table['title'] ?? null),
                'headers' => $headers,
                'rows' => $rows,
            ];
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $normalized = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            $trimmed = mb_trim($value);

            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }

        return $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return DocumentExtractionPayload
     */
    private function emptyPayload(): array
    {
        return [
            'document_type' => null,
            'summary' => null,
            'fields' => [],
            'tables' => [],
            'notes' => [],
        ];
    }
}
