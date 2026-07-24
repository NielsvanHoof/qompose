<?php

declare(strict_types=1);

namespace App\Services\Ocr;

use App\Contracts\Ocr\DescribesDocumentOverview;
use App\Models\UploadedDocument;
use App\Services\Ocr\Normalization\FieldSensitivityClassifier;
use App\Services\Ocr\Normalization\TextractFormsTablesMapper;
use App\Services\Ocr\Textract\TextractJobBlockFetcher;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;

use function count;
use function json_encode;

/**
 * Fetch Textract blocks, map forms/tables, enrich with Bedrock overview, classify PII.
 * Returns the JSON string stored on uploaded_documents.extracted_text.
 */
final class TextractExtractionPipeline
{
    public function __construct(
        private readonly TextractJobBlockFetcher $blockFetcher,
        private readonly TextractFormsTablesMapper $formsTablesMapper,
        private readonly DescribesDocumentOverview $describesDocumentOverview,
        private readonly FieldSensitivityClassifier $fieldSensitivityClassifier,
    ) {}

    public function run(string $jobId, UploadedDocument $document): string
    {
        $blocks = $this->blockFetcher->fetch($jobId);
        $structured = $this->formsTablesMapper->map($blocks);

        // Counts only — never log field values (may contain BSN / account numbers).
        Log::info('OCR: mapped Textract FORMS/TABLES — calling Bedrock overview.', [
            'uploaded_document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'textract_job_id' => $jobId,
            'block_count' => count($blocks),
            'field_count' => count($structured['fields']),
            'table_count' => count($structured['tables']),
        ]);

        $overview = $this->describesDocumentOverview->describe($structured);

        $structured['document_type'] = $overview['document_type'];
        $structured['summary'] = $overview['summary'];
        $structured['notes'] = $overview['notes'];
        $structured = $this->fieldSensitivityClassifier->classify($structured);

        try {
            return json_encode(
                $structured,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to encode structured OCR JSON.', 0, $exception);
        }
    }
}
