<?php

declare(strict_types=1);

namespace App\Actions\Ocr;

use App\Actions\Audit\LogAuditActivityAction;
use App\Contracts\Ocr\DescribesDocumentOverview;
use App\Enums\AuditEvent;
use App\Enums\DocumentProcessingStatus;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use App\Services\Ocr\Normalization\FieldSensitivityClassifier;
use App\Services\Ocr\Normalization\TextractFormsTablesMapper;
use App\Services\Ocr\Textract\TextractJobBlockFetcher;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Throwable;

use function count;
use function in_array;
use function is_string;
use function json_encode;

/**
 * Applies a Textract AnalyzeDocument SNS completion to an UploadedDocument.
 * Maps FORMS/TABLES, asks Bedrock for overview metadata, tags PII, stores JSON.
 */
final class CompleteTextractExtractionAction
{
    public function __construct(
        private readonly TextractJobBlockFetcher $blockFetcher,
        private readonly TextractFormsTablesMapper $formsTablesMapper,
        private readonly DescribesDocumentOverview $describesDocumentOverview,
        private readonly FieldSensitivityClassifier $fieldSensitivityClassifier,
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * @param  array{JobId?: mixed, Status?: mixed, StatusMessage?: mixed, API?: mixed}  $notification
     */
    public function handle(array $notification): bool
    {
        $jobId = $notification['JobId'] ?? null;
        $status = $notification['Status'] ?? null;

        if (! is_string($jobId) || $jobId === '') {
            Log::warning('OCR: Textract completion ignored — missing JobId.');

            return false;
        }

        if (! is_string($status) || $status === '') {
            Log::warning('OCR: Textract completion ignored — missing Status.', [
                'textract_job_id' => $jobId,
            ]);

            return false;
        }

        // Only AnalyzeDocument completions — ignore legacy DetectDocumentText jobs.
        $api = $notification['API'] ?? null;
        if (is_string($api) && $api !== '' && $api !== 'StartDocumentAnalysis') {
            Log::info('OCR: Textract completion ignored — unexpected API.', [
                'textract_job_id' => $jobId,
                'status' => $status,
                'api' => $api,
            ]);

            return false;
        }

        $document = UploadedDocument::query()
            ->withoutGlobalScopes()
            ->where('textract_job_id', $jobId)
            ->first();

        if (! $document instanceof UploadedDocument) {
            // Common when SQS still has old messages or a job was re-started with a new id.
            Log::warning('OCR: Textract completion has no matching uploaded document.', [
                'textract_job_id' => $jobId,
                'status' => $status,
                'api' => is_string($api) ? $api : null,
            ]);

            return false;
        }

        // SQS consumer has no HTTP tenant context — switch for audits and scoped queries.
        $tenant = Tenant::query()->find($document->tenant_id);
        if (! $tenant instanceof Tenant) {
            Log::error('OCR: Textract completion document has missing tenant.', [
                'uploaded_document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'textract_job_id' => $jobId,
            ]);

            return false;
        }

        return $this->tenantContext->runForTenant($tenant, function () use ($document, $notification, $jobId, $status): bool {
            if (in_array($document->processing_status, [
                DocumentProcessingStatus::Completed,
                DocumentProcessingStatus::Failed,
            ], true)) {
                Log::info('OCR: Textract completion already applied — skipping.', [
                    'uploaded_document_id' => $document->id,
                    'tenant_id' => $document->tenant_id,
                    'textract_job_id' => $jobId,
                ]);

                return true;
            }

            Log::info('OCR: applying Textract completion notification.', [
                'uploaded_document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'textract_job_id' => $jobId,
                'status' => $status,
                'original_filename' => $document->original_filename,
            ]);

            if ($status === 'SUCCEEDED') {
                $startedAt = hrtime(true);
                $extractedJson = $this->fetchExtractedJson($jobId, $document);
                $durationMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);

                $updated = UploadedDocument::query()
                    ->whereKey($document->id)
                    ->where('path', $document->path)
                    ->where('textract_job_id', $jobId)
                    ->where('processing_status', DocumentProcessingStatus::Processing->value)
                    ->update([
                        'processing_status' => DocumentProcessingStatus::Completed->value,
                        'extracted_text' => $extractedJson,
                        'processing_error' => null,
                        'processing_finished_at' => now(),
                    ]);

                if ($updated !== 1) {
                    Log::warning('OCR: discarded stale Textract completion.', [
                        'uploaded_document_id' => $document->id,
                        'tenant_id' => $document->tenant_id,
                        'textract_job_id' => $jobId,
                        'expected_path' => $document->path,
                    ]);

                    return true;
                }

                $document->refresh();

                $this->logAuditActivity->handle(
                    AuditEvent::DocumentProcessingCompleted,
                    $document,
                    [
                        'original_filename' => $document->original_filename,
                        'extracted_length' => mb_strlen($extractedJson),
                        'textract_job_id' => $jobId,
                    ],
                    includeRequestContext: false,
                );

                Log::info('OCR: Textract extraction completed successfully.', [
                    'uploaded_document_id' => $document->id,
                    'tenant_id' => $document->tenant_id,
                    'textract_job_id' => $jobId,
                    'extracted_length' => mb_strlen($extractedJson),
                    'duration_ms' => $durationMs,
                ]);

                return true;
            }

            if (in_array($status, ['FAILED', 'ERROR'], true)) {
                $message = $notification['StatusMessage'] ?? "Textract job reported {$status}.";
                $error = is_string($message) && $message !== ''
                    ? mb_substr($message, 0, 500)
                    : "Textract job reported {$status}.";

                $this->markFailed($document, $jobId, $error, $status);

                return true;
            }

            // Unknown/non-final status — leave the message visible for another poll.
            Log::info('OCR: Textract completion deferred — non-final status.', [
                'uploaded_document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'textract_job_id' => $jobId,
                'status' => $status,
            ]);

            return false;
        });
    }

    public function failPermanently(string $jobId, Throwable $exception): bool
    {
        $document = UploadedDocument::query()
            ->withoutGlobalScopes()
            ->where('textract_job_id', $jobId)
            ->first();

        if (! $document instanceof UploadedDocument) {
            Log::warning('OCR: permanent completion failure has no matching uploaded document.', [
                'textract_job_id' => $jobId,
            ]);

            return false;
        }

        $tenant = Tenant::query()->find($document->tenant_id);

        if (! $tenant instanceof Tenant) {
            Log::error('OCR: permanent completion failure document has missing tenant.', [
                'uploaded_document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'textract_job_id' => $jobId,
            ]);

            return false;
        }

        return $this->tenantContext->runForTenant($tenant, function () use ($document, $exception, $jobId): bool {
            if (in_array($document->processing_status, [
                DocumentProcessingStatus::Completed,
                DocumentProcessingStatus::Failed,
            ], true)) {
                return true;
            }

            $error = mb_substr($exception->getMessage(), 0, 500);

            if ($error === '') {
                $error = 'Textract result processing failed permanently.';
            }

            $this->markFailed($document, $jobId, $error, 'RESULT_PROCESSING_FAILED');

            return true;
        });
    }

    private function markFailed(
        UploadedDocument $document,
        string $jobId,
        string $error,
        string $status,
    ): void {
        $updated = UploadedDocument::query()
            ->whereKey($document->id)
            ->where('path', $document->path)
            ->where('textract_job_id', $jobId)
            ->where('processing_status', DocumentProcessingStatus::Processing->value)
            ->update([
                'processing_status' => DocumentProcessingStatus::Failed->value,
                'processing_error' => $error,
                'processing_finished_at' => now(),
            ]);

        if ($updated !== 1) {
            Log::warning('OCR: discarded stale Textract failure.', [
                'uploaded_document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'textract_job_id' => $jobId,
                'expected_path' => $document->path,
                'status' => $status,
            ]);

            return;
        }

        $document->refresh();

        $this->logAuditActivity->handle(
            AuditEvent::DocumentProcessingFailed,
            $document,
            [
                'original_filename' => $document->original_filename,
                'error' => $error,
                'textract_job_id' => $jobId,
                'textract_status' => $status,
            ],
            includeRequestContext: false,
        );

        Log::error('OCR: Textract processing failed.', [
            'uploaded_document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'textract_job_id' => $jobId,
            'status' => $status,
            'error' => $error,
        ]);
    }

    private function fetchExtractedJson(string $jobId, UploadedDocument $document): string
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
