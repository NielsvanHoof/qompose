<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Audit\LogAuditActivity;
use App\Actions\Dossiers\UploadDocumentForRequest;
use App\Enums\AuditEvent;
use App\Enums\DossierStatus;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Http\Requests\Portal\StorePortalUploadedDocumentRequest;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\UploadedDocument;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class ClientPortalUploadController extends Controller
{
    /**
     * Accept a client upload for one document request on the grant's dossier.
     */
    public function store(
        StorePortalUploadedDocumentRequest $request,
        string $token,
        int $documentRequest,
        UploadDocumentForRequest $uploadDocumentForRequest,
        LogAuditActivity $logAuditActivity,
    ): RedirectResponse {
        $grant = $this->grantFromRequest($request);

        // Resolve after tenant is current (middleware). Scope + dossier check prevent cross-dossier uploads.
        $documentRequestModel = DocumentRequest::query()->findOrFail($documentRequest);

        abort_unless($documentRequestModel->dossier_id === $grant->dossier_id, 404);

        $file = $request->file('document');

        if ($file === null) {
            return back()->withErrors(['document' => 'A document file is required.']);
        }

        $uploadDocumentForRequest(
            $documentRequestModel,
            $file,
            static function (
                UploadedDocument $uploadedDocument,
                DocumentRequest $lockedDocumentRequest,
            ) use ($grant, $logAuditActivity): void {
                // Mark dossier as awaiting review once the client starts delivering files.
                $dossier = $lockedDocumentRequest->dossier;

                if (! $dossier instanceof Dossier) {
                    abort(404);
                }

                if ($dossier->status === DossierStatus::Draft || $dossier->status === DossierStatus::AwaitingClient) {
                    $dossier->update(['status' => DossierStatus::InReview]);
                }

                $grant->forceFill(['last_used_at' => now()])->save();

                $logAuditActivity(
                    AuditEvent::DocumentUploaded,
                    $uploadedDocument,
                    [
                        'source' => 'client_portal',
                        'access_grant_id' => $grant->id,
                    ],
                );
            },
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Document uploaded. Thank you.',
        ]);

        return to_route('portal.show', ['token' => $token]);
    }

    private function grantFromRequest(StorePortalUploadedDocumentRequest $request): ClientAccessGrant
    {
        $grant = $request->attributes->get(ResolveClientPortalGrant::REQUEST_ATTRIBUTE);

        if (! $grant instanceof ClientAccessGrant) {
            abort(404);
        }

        return $grant;
    }
}
