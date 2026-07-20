<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Actions\Audit\LogAuditActivity;
use App\Actions\Dossiers\UploadDocumentForRequest;
use App\Enums\AuditEvent;
use App\Enums\SubmissionContext;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\UploadedDocument;
use App\Transitions\DossierTransitions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;

final class SubmitPortalUpload
{
    public function __construct(
        private readonly UploadDocumentForRequest $uploadDocumentForRequest,
        private readonly LogAuditActivity $logAuditActivity,
        private readonly DossierTransitions $dossierTransitions,
    ) {}

    public function handle(
        DocumentRequest $documentRequest,
        ClientAccessGrant $grant,
        UploadedFile $file,
    ): UploadedDocument {
        return $this->uploadDocumentForRequest->handle(
            $documentRequest,
            $file,
            function (
                UploadedDocument $uploadedDocument,
                DocumentRequest $lockedDocumentRequest,
            ) use ($grant): void {
                $dossierQuery = Dossier::query()->whereKey($lockedDocumentRequest->dossier_id);
                $dossierQuery->getQuery()->lockForUpdate();
                $dossier = $dossierQuery->firstOrFail();

                $this->dossierTransitions->markInReview($dossier);

                $grantQuery = ClientAccessGrant::query()->whereKey($grant->getKey());
                $grantQuery->getQuery()->lockForUpdate();
                $lockedGrant = $grantQuery->firstOrFail();

                if ($lockedGrant->dossier_id !== $dossier->id) {
                    throw (new ModelNotFoundException)->setModel(
                        DocumentRequest::class,
                        [$lockedDocumentRequest->getKey()],
                    );
                }

                $lockedGrant->forceFill(['last_used_at' => now()])->save();

                $this->logAuditActivity->handle(
                    AuditEvent::DocumentUploaded,
                    $uploadedDocument,
                    [
                        'source' => 'client_portal',
                        'access_grant_id' => $lockedGrant->id,
                    ],
                );
            },
            SubmissionContext::Portal,
        );
    }
}
