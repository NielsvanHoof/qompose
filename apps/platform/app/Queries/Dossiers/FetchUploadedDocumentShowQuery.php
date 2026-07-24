<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Contracts\Ocr\DocumentExtraction;
use App\Models\UploadedDocument;
use JsonException;

use function is_array;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function json_decode;

/**
 * Shape the OCR extraction page props for Inertia.
 *
 * @phpstan-import-type DocumentExtractionPayload from DocumentExtraction
 */
final class FetchUploadedDocumentShowQuery
{
    /**
     * @return array{
     *     uploaded_document: array{
     *         id: int,
     *         original_filename: string,
     *         size_bytes: int,
     *         uploaded_at: string,
     *         processing_status: string,
     *         processing_error: string|null,
     *         extraction: DocumentExtractionPayload|null,
     *         raw_json: string|null
     *     },
     *     document_request: array{id: int, title: string}|null,
     *     dossier: array{id: int, title: string}|null,
     *     can_download: bool
     * }
     */
    public function handle(UploadedDocument $uploadedDocument): array
    {
        $uploadedDocument->load([
            'documentRequest:id,dossier_id,title,tenant_id',
            'documentRequest.dossier:id,title,tenant_id',
        ]);

        $documentRequest = $uploadedDocument->documentRequest;
        $dossier = $documentRequest?->dossier;

        return [
            'uploaded_document' => [
                'id' => $uploadedDocument->id,
                'original_filename' => $uploadedDocument->original_filename,
                'size_bytes' => $uploadedDocument->size_bytes,
                'uploaded_at' => $uploadedDocument->uploaded_at->toIso8601String(),
                'processing_status' => $uploadedDocument->processing_status->value,
                'processing_error' => $uploadedDocument->processing_error,
                'extraction' => $this->parseExtraction($uploadedDocument->extracted_text),
                'raw_json' => $uploadedDocument->extracted_text,
            ],
            'document_request' => $documentRequest === null ? null : [
                'id' => $documentRequest->id,
                'title' => $documentRequest->title,
            ],
            'dossier' => $dossier === null ? null : [
                'id' => $dossier->id,
                'title' => $dossier->title,
            ],
            'can_download' => request()->user()?->can('download', $uploadedDocument) ?? false,
        ];
    }

    /**
     * Decode stored Bedrock-structured OCR JSON into a typed extraction payload.
     *
     * Trusts our own OCR-written shape; only guards against missing/invalid JSON.
     *
     * @return DocumentExtractionPayload|null
     */
    private function parseExtraction(?string $raw): ?array
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $fields = $decoded['fields'] ?? [];
        $tables = $decoded['tables'] ?? [];
        $notes = $decoded['notes'] ?? [];

        if (! is_array($fields) || ! is_array($tables) || ! is_array($notes)) {
            return null;
        }

        $documentType = $decoded['document_type'] ?? null;
        $summary = $decoded['summary'] ?? null;
        $confidence = $decoded['confidence'] ?? null;

        /** @var DocumentExtractionPayload $payload */
        $payload = [
            'document_type' => is_string($documentType) ? $documentType : null,
            'summary' => is_string($summary) ? $summary : null,
            'fields' => $this->normalizeExtractionFields(array_values($fields)),
            'tables' => array_values($tables),
            'notes' => array_values($notes),
            'confidence' => $this->nullableFloat($confidence),
        ];

        return $payload;
    }

    /**
     * Normalize field rows into the typed extraction shape for Inertia.
     *
     * @param  list<mixed>  $fields
     * @return list<array{label: string, value: mixed, confidence: float|null, sensitivity: string|null}>
     */
    private function normalizeExtractionFields(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $label = $field['label'] ?? null;

            if (! is_string($label) || $label === '') {
                continue;
            }

            $sensitivity = $field['sensitivity'] ?? null;

            $normalized[] = [
                'label' => $label,
                'value' => $field['value'] ?? '',
                'confidence' => $this->nullableFloat($field['confidence'] ?? null),
                'sensitivity' => is_string($sensitivity) && $sensitivity !== '' ? $sensitivity : null,
            ];
        }

        return $normalized;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 4);
        }

        if (is_string($value) && is_numeric($value)) {
            return round((float) $value, 4);
        }

        return null;
    }
}
