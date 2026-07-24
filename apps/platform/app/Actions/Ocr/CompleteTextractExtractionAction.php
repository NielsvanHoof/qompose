<?php

declare(strict_types=1);

namespace App\Actions\Ocr;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Enums\DocumentProcessingStatus;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use App\Services\Ocr\TextractExtractionPipeline;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Log;

use function in_array;
use function is_string;

/**
 * Applies a Textract AnalyzeDocument SNS completion to an UploadedDocument.
 * Maps FORMS/TABLES via the extraction pipeline, then persists status + audit.
 */
final class CompleteTextractExtractionAction
{
    public function __construct(
        private readonly TextractExtractionPipeline $extractionPipeline,
        private readonly MarkTextractExtractionFailedAction $markTextractExtractionFailed,
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
                $extractedJson = $this->extractionPipeline->run($jobId, $document);
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

                $this->markTextractExtractionFailed->handle($document, $jobId, $error, $status);

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
}
