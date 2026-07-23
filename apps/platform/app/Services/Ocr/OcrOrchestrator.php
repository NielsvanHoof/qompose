<?php

declare(strict_types=1);

namespace App\Services\Ocr;

use App\Actions\Audit\LogAuditActivityAction;
use App\Contracts\Ocr\StartsDocumentOcr;
use App\Enums\AuditEvent;
use App\Enums\DocumentProcessingStatus;
use App\Enums\OcrProcessingOutcome;
use App\Models\UploadedDocument;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates OCR start and immediate completion audit logging.
 * Deferred adapters leave the document processing until async completion.
 */
final class OcrOrchestrator
{
    public function __construct(
        private readonly StartsDocumentOcr $ocr,
        private readonly LogAuditActivityAction $logAuditActivity,
    ) {}

    public function startProcessing(UploadedDocument $document): OcrProcessingOutcome
    {
        Log::info('OCR: orchestrator starting document processing.', [
            'uploaded_document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'original_filename' => $document->original_filename,
            'processing_status' => $document->processing_status->value,
        ]);

        $this->logAuditActivity->handle(
            AuditEvent::DocumentProcessingStarted,
            $document,
            ['original_filename' => $document->original_filename],
            includeRequestContext: false,
        );

        $this->ocr->start($document);
        $document->refresh();

        if ($document->processing_status === DocumentProcessingStatus::Completed) {
            $this->logAuditActivity->handle(
                AuditEvent::DocumentProcessingCompleted,
                $document,
                [
                    'original_filename' => $document->original_filename,
                    'extracted_length' => mb_strlen((string) $document->extracted_text),
                ],
                includeRequestContext: false,
            );

            Log::info('OCR: orchestrator completed immediately (sync driver).', [
                'uploaded_document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'outcome' => OcrProcessingOutcome::Immediate->value,
                'extracted_length' => mb_strlen((string) $document->extracted_text),
            ]);

            return OcrProcessingOutcome::Immediate;
        }

        Log::info('OCR: orchestrator deferred — waiting for async provider completion.', [
            'uploaded_document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'outcome' => OcrProcessingOutcome::Deferred->value,
            'textract_job_id' => $document->textract_job_id,
            'processing_status' => $document->processing_status->value,
        ]);

        return OcrProcessingOutcome::Deferred;
    }
}
