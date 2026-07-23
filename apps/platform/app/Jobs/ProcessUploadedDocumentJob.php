<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Enums\DocumentProcessingStatus;
use App\Models\UploadedDocument;
use App\Services\Ocr\OcrOrchestrator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

use function hash;

/**
 * Async OCR pipeline for an uploaded document.
 * Mock driver completes in-process; Textract stays processing until SNS/SQS completion.
 */
final class ProcessUploadedDocumentJob implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    /**
     * The upload revision this job is allowed to process.
     *
     * Null keeps jobs queued before this field was introduced deploy-safe.
     */
    public ?string $expectedPath = null;

    /**
     * How many times the job may be attempted before failing permanently.
     */
    public int $tries = 3;

    /**
     * Seconds to wait between retries.
     *
     * @var list<int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(public int $uploadedDocumentId, ?string $expectedPath = null)
    {
        $this->expectedPath = $expectedPath;
    }

    public function uniqueId(): string
    {
        return $this->uploadedDocumentId.':'.hash('sha256', $this->expectedPath ?? 'legacy');
    }

    public function handle(
        OcrOrchestrator $ocrOrchestrator,
        LogAuditActivityAction $logAuditActivity,
    ): void {
        $document = UploadedDocument::query()->find($this->uploadedDocumentId);

        if (! $document instanceof UploadedDocument) {
            return;
        }

        $expectedPath = $this->expectedPath ?? $document->path;

        // Idempotency: a completed extraction must never re-run.
        if ($document->processing_status === DocumentProcessingStatus::Completed) {
            Log::info('OCR: skipped ProcessUploadedDocumentJob — already completed.', [
                'uploaded_document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
            ]);

            return;
        }

        $claimed = $this->claimForProcessing($document, $expectedPath);

        if (! $claimed instanceof UploadedDocument) {
            Log::info('OCR: skipped ProcessUploadedDocumentJob — could not claim document.', [
                'uploaded_document_id' => $this->uploadedDocumentId,
                'expected_path' => $expectedPath,
            ]);

            return;
        }

        Log::info('OCR: ProcessUploadedDocumentJob claimed document.', [
            'uploaded_document_id' => $claimed->id,
            'tenant_id' => $claimed->tenant_id,
            'attempt' => $this->attempts(),
            'original_filename' => $claimed->original_filename,
        ]);

        try {
            $ocrOrchestrator->startProcessing($claimed);
        } catch (Throwable $exception) {
            // Let the queue retry until tries are exhausted.
            if ($this->attempts() < $this->tries) {
                Log::warning('OCR: ProcessUploadedDocumentJob failed — will retry.', [
                    'uploaded_document_id' => $claimed->id,
                    'tenant_id' => $claimed->tenant_id,
                    'attempt' => $this->attempts(),
                    'tries' => $this->tries,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }

            $error = mb_substr($exception->getMessage(), 0, 500);
            $updated = UploadedDocument::query()
                ->whereKey($claimed->id)
                ->where('path', $expectedPath)
                ->update([
                    'processing_status' => DocumentProcessingStatus::Failed->value,
                    'processing_error' => $error,
                    'processing_finished_at' => now(),
                ]);

            if ($updated !== 1) {
                Log::warning('OCR: discarded permanent failure for a stale upload revision.', [
                    'uploaded_document_id' => $claimed->id,
                    'tenant_id' => $claimed->tenant_id,
                    'expected_path' => $expectedPath,
                    'error' => $error,
                ]);

                throw $exception;
            }

            $claimed->refresh();

            Log::error('OCR: ProcessUploadedDocumentJob failed permanently.', [
                'uploaded_document_id' => $claimed->id,
                'tenant_id' => $claimed->tenant_id,
                'attempt' => $this->attempts(),
                'error' => $error,
            ]);

            $logAuditActivity->handle(
                AuditEvent::DocumentProcessingFailed,
                $claimed,
                [
                    'original_filename' => $claimed->original_filename,
                    'error' => $claimed->processing_error,
                ],
                includeRequestContext: false,
            );

            throw $exception;
        }
    }

    /**
     * Atomically move pending/failed/processing → processing.
     * Returns null when another worker already completed the document.
     */
    private function claimForProcessing(
        UploadedDocument $document,
        string $expectedPath,
    ): ?UploadedDocument {
        return DB::transaction(function () use ($document, $expectedPath): ?UploadedDocument {
            $query = UploadedDocument::query()->whereKey($document->getKey());
            $query->getQuery()->lockForUpdate();

            $locked = $query->first();

            if (! $locked instanceof UploadedDocument) {
                return null;
            }

            if ($locked->path !== $expectedPath) {
                return null;
            }

            if (
                $locked->processing_status === DocumentProcessingStatus::Completed
                || (
                    $locked->processing_status === DocumentProcessingStatus::Processing
                    && $locked->textract_job_id !== null
                )
            ) {
                return null;
            }

            $locked->forceFill([
                'processing_status' => DocumentProcessingStatus::Processing,
                'processing_error' => null,
                'extracted_text' => null,
                'textract_job_id' => null,
                'processing_started_at' => $locked->processing_started_at ?? now(),
                'processing_finished_at' => null,
            ])->save();

            return $locked;
        });
    }
}
