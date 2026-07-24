<?php

declare(strict_types=1);

namespace App\Actions\Ocr;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Enums\DocumentProcessingStatus;
use App\Models\UploadedDocument;
use Illuminate\Support\Facades\Log;

/**
 * Persist a terminal Textract failure on the uploaded document and audit it.
 * Shared by completion handling and permanent SQS-receive failure paths.
 */
final class MarkTextractExtractionFailedAction
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
    ) {}

    public function handle(
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
}
