<?php

declare(strict_types=1);

namespace App\Services\Ocr;

use App\Contracts\Ocr\StartsDocumentOcr;
use App\Enums\DocumentProcessingStatus;
use App\Models\UploadedDocument;
use JsonException;

use function json_encode;

/**
 * POC / CI stand-in for a real OCR provider.
 * Returns deterministic FORMS/TABLES-shaped JSON so demos and tests stay stable offline.
 */
final class MockOcrExtractor implements StartsDocumentOcr
{
    /**
     * Build a fake extraction payload from the uploaded file metadata.
     * Does not call external APIs — safe without AWS credentials.
     */
    public function start(UploadedDocument $document): void
    {
        $extractedText = $this->buildExtractedText($document);

        $document->forceFill([
            'processing_status' => DocumentProcessingStatus::Completed,
            'extracted_text' => $extractedText,
            'processing_error' => null,
            'processing_finished_at' => now(),
            'textract_job_id' => null,
        ])->save();
    }

    /**
     * Kept for unit-level assertions that only need the payload.
     */
    public function extract(UploadedDocument $document): string
    {
        return $this->buildExtractedText($document);
    }

    private function buildExtractedText(UploadedDocument $document): string
    {
        $filename = $document->original_filename;
        $reference = 'MOCK-'.mb_strtoupper(mb_substr(hash('xxh3', $filename), 0, 8));

        try {
            return json_encode([
                'key_values' => [
                    'Source file' => $filename,
                    'Detected mime type' => $document->mime_type,
                    'Document type' => 'identity / statement / other',
                    'Reference' => $reference,
                    'Confidence' => '0.97 (mock)',
                ],
                'tables' => [
                    [
                        ['Field', 'Value'],
                        ['Approximate size (KB)', (string) max(1, (int) round($document->size_bytes / 1024))],
                    ],
                ],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (JsonException $exception) {
            return '{"key_values":{},"tables":[]}';
        }
    }
}
