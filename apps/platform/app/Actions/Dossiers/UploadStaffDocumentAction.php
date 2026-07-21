<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Enums\SubmissionContext;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\UploadedDocument;
use App\Transitions\DossierTransitions;
use Illuminate\Http\UploadedFile;

/**
 * Staff upload: store the file, mark the dossier in review, and audit.
 */
final class UploadStaffDocumentAction
{
    public function __construct(
        private readonly UploadDocumentForRequestAction $uploadDocumentForRequest,
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly DossierTransitions $dossierTransitions,
    ) {}

    public function handle(
        DocumentRequest $documentRequest,
        UploadedFile $file,
    ): UploadedDocument {
        return $this->uploadDocumentForRequest->handle(
            $documentRequest,
            $file,
            function (
                UploadedDocument $uploadedDocument,
                DocumentRequest $lockedDocumentRequest,
            ): void {
                $dossierQuery = Dossier::query()->whereKey($lockedDocumentRequest->dossier_id);
                $dossierQuery->getQuery()->lockForUpdate();
                $lockedDossier = $dossierQuery->firstOrFail();

                $this->dossierTransitions->markInReview($lockedDossier);

                $this->logAuditActivity->handle(
                    AuditEvent::DocumentUploaded,
                    $uploadedDocument,
                    [
                        'source' => 'staff',
                        'original_filename' => $uploadedDocument->original_filename,
                    ],
                );
            },
            SubmissionContext::Staff,
        );
    }
}
