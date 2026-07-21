<?php

declare(strict_types=1);

namespace App\Services\Ocr;

use App\Actions\Audit\LogAuditActivityAction;
use App\Contracts\Ocr\StartsDocumentOcr;
use App\Enums\AuditEvent;
use App\Enums\DocumentProcessingStatus;
use App\Enums\OcrProcessingOutcome;
use App\Models\UploadedDocument;

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

            return OcrProcessingOutcome::Immediate;
        }

        return OcrProcessingOutcome::Deferred;
    }
}
