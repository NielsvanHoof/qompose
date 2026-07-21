<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Enums\DocumentProcessingStatus;
use App\Models\UploadedDocument;
use App\Services\Ocr\OcrOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Async OCR pipeline for an uploaded document.
 * Mock driver completes in-process; Textract stays processing until SNS/SQS completion.
 */
final class ProcessUploadedDocument implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

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

    public function __construct(public int $uploadedDocumentId)
    {
        //
    }

    public function handle(
        OcrOrchestrator $ocrOrchestrator,
        LogAuditActivityAction $logAuditActivity,
    ): void {
        $document = UploadedDocument::query()->find($this->uploadedDocumentId);

        if (! $document instanceof UploadedDocument) {
            return;
        }

        // Idempotency: a completed extraction must never re-run.
        if ($document->processing_status === DocumentProcessingStatus::Completed) {
            return;
        }

        $claimed = $this->claimForProcessing($document);

        if (! $claimed instanceof UploadedDocument) {
            return;
        }

        try {
            $ocrOrchestrator->startProcessing($claimed);
        } catch (Throwable $exception) {
            // Let the queue retry until tries are exhausted.
            if ($this->attempts() < $this->tries) {
                throw $exception;
            }

            $claimed->forceFill([
                'processing_status' => DocumentProcessingStatus::Failed,
                'processing_error' => mb_substr($exception->getMessage(), 0, 500),
                'processing_finished_at' => now(),
            ])->save();

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
    private function claimForProcessing(UploadedDocument $document): ?UploadedDocument
    {
        return DB::transaction(function () use ($document): ?UploadedDocument {
            $query = UploadedDocument::query()->whereKey($document->getKey());
            $query->getQuery()->lockForUpdate();

            $locked = $query->first();

            if (! $locked instanceof UploadedDocument) {
                return null;
            }

            if ($locked->processing_status === DocumentProcessingStatus::Completed) {
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
