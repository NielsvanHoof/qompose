<?php

declare(strict_types=1);

namespace App\Services\Ocr\Normalization;

use App\Contracts\Ocr\DescribesDocumentOverview;
use App\Contracts\Ocr\DocumentExtraction;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;

use function count;
use function is_array;
use function is_string;
use function json_decode;
use function mb_strlen;

/**
 * Asks Bedrock for document_type / summary / notes only.
 * Fields and tables stay Textract-owned so confidence is preserved.
 *
 * @phpstan-import-type DocumentExtractionPayload from DocumentExtraction
 * @phpstan-import-type DocumentOverview from DocumentExtraction
 */
final class BedrockDocumentOverviewNormalizer implements DescribesDocumentOverview
{
    public function __construct(
        private readonly BedrockRuntimeClient $bedrock,
        private readonly Repository $config,
    ) {}

    /**
     * @param  DocumentExtractionPayload  $payload
     * @return DocumentOverview
     */
    public function describe(array $payload): array
    {
        $context = $this->buildContext($payload);

        if ($context === '') {
            Log::info('OCR: Bedrock overview skipped — empty Textract context.');

            return $this->emptyOverview();
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
                        ['text' => $this->userPrompt($context)],
                    ],
                ],
            ],
            'inferenceConfig' => [
                'maxTokens' => max(512, min(2048, $maxTokens)),
                'temperature' => max(0.0, min(1.0, $temperature)),
            ],
        ];

        if ($this->usesOpenAiGptOss($modelId)) {
            $converseArgs['additionalModelRequestFields'] = [
                'reasoning_effort' => 'low',
            ];
        }

        // Context may include PII labels — log length only, never the prompt body.
        Log::info('OCR: calling Bedrock Converse for document overview.', [
            'model_id' => $modelId,
            'context_length' => mb_strlen($context),
            'max_tokens' => $converseArgs['inferenceConfig']['maxTokens'],
        ]);

        $startedAt = hrtime(true);
        $result = $this->bedrock->converse($converseArgs);
        $durationMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);
        $text = $this->extractAssistantText($result->toArray());
        $overview = $this->decodeAndNormalize($text);

        Log::info('OCR: Bedrock overview completed.', [
            'model_id' => $modelId,
            'duration_ms' => $durationMs,
            'document_type' => $overview['document_type'],
            'note_count' => count($overview['notes']),
            'response_length' => mb_strlen($text),
        ]);

        return $overview;
    }

    private function usesOpenAiGptOss(string $modelId): bool
    {
        return str_contains($modelId, 'gpt-oss') || str_contains($modelId, 'openai.');
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You classify OCR extractions from Dutch and international financial/identity documents
(payslips, bank statements, invoices, ID cards, tax forms).

Return ONLY valid JSON matching this schema (no markdown fences, no commentary):
{
  "document_type": string|null,
  "summary": string|null,
  "notes": string[]
}

Rules:
- document_type: short snake_case label when clear (payslip, bank_statement, invoice, identity, tax_form, other), else null.
- summary: one short sentence describing the document, or null.
- notes: short caveats about the extraction quality. Empty array when none.
- Do not invent field values. Do not return fields or tables.
- Keep summary language matching the document when obvious (Dutch documents → Dutch summary).
PROMPT;
    }

    private function userPrompt(string $context): string
    {
        return "Extracted form fields and table outlines:\n\n{$context}";
    }

    /**
     * Compact context so Bedrock sees labels/values without re-structuring them.
     *
     * @param  DocumentExtractionPayload  $payload
     */
    private function buildContext(array $payload): string
    {
        $lines = [];

        foreach ($payload['fields'] as $field) {
            $value = $field['value'];
            $rendered = is_array($value) ? implode(', ', $value) : $value;
            $lines[] = "- {$field['label']}: {$rendered}";
        }

        foreach ($payload['tables'] as $index => $table) {
            $title = $table['title'] ?? null;
            $label = is_string($title) && $title !== '' ? $title : 'Table '.($index + 1);
            $headerCount = count($table['headers']);
            $rowCount = count($table['rows']);
            $lines[] = "- [{$label}] headers={$headerCount} rows={$rowCount}";
        }

        return implode("\n", $lines);
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

        $joinedReasoning = mb_trim(implode("\n", $reasoningParts));

        if ($joinedReasoning !== '') {
            throw new RuntimeException(
                'Bedrock returned reasoning but no answer text. Increase OCR_BEDROCK_MAX_TOKENS or lower reasoning effort. stopReason='.
                (is_string($result['stopReason'] ?? null) ? $result['stopReason'] : 'unknown'),
            );
        }

        throw new RuntimeException(
            'Bedrock returned an empty response. stopReason='.
            (is_string($result['stopReason'] ?? null) ? $result['stopReason'] : 'unknown'),
        );
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function extractReasoningText(array $block): ?string
    {
        $reasoningContent = $block['reasoningContent'] ?? null;

        if (! is_array($reasoningContent)) {
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
     * @return DocumentOverview
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
            'notes' => $this->normalizeStringList($decoded['notes'] ?? []),
        ];
    }

    private function unwrapJson(string $text): string
    {
        $trimmed = mb_trim($text);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $trimmed, $matches) === 1) {
            return mb_trim($matches[1]);
        }

        return $trimmed;
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
     * @return DocumentOverview
     */
    private function emptyOverview(): array
    {
        return [
            'document_type' => null,
            'summary' => null,
            'notes' => [],
        ];
    }
}
