<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Audit\LogAuditActivity;
use App\Actions\Dossiers\ResolveDocumentTemporaryUrl;
use App\Actions\Dossiers\UploadDocumentForRequest;
use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\StoreUploadedDocumentRequest;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use App\Transitions\DossierTransitions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UploadedDocumentController extends Controller
{
    public function store(
        Tenant $tenant,
        StoreUploadedDocumentRequest $request,
        Dossier $dossier,
        DocumentRequest $documentRequest,
        UploadDocumentForRequest $uploadDocumentForRequest,
        LogAuditActivity $logAuditActivity,
        DossierTransitions $dossierTransitions,
    ): RedirectResponse {
        // Parent ownership is enforced by scoped route bindings
        // (dossier → documentRequest). Tenant scope is already applied.
        $this->authorize('view', $dossier);

        $file = $request->file('document');

        if ($file === null) {
            return back()->withErrors(['document' => 'A document file is required.']);
        }

        $uploadDocumentForRequest->handle(
            $documentRequest,
            $file,
            function (UploadedDocument $uploadedDocument, DocumentRequest $lockedDocumentRequest) use (
                $logAuditActivity,
                $dossierTransitions,
            ): void {
                $dossierQuery = Dossier::query()->whereKey($lockedDocumentRequest->dossier_id);
                $dossierQuery->getQuery()->lockForUpdate();
                $lockedDossier = $dossierQuery->firstOrFail();

                $dossierTransitions->markInReview($lockedDossier);

                $logAuditActivity->handle(
                    AuditEvent::DocumentUploaded,
                    $uploadedDocument,
                    [
                        'source' => 'staff',
                        'original_filename' => $uploadedDocument->original_filename,
                    ],
                );
            },
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Document uploaded.',
        ]);

        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }

    /**
     * Authorize, audit, then either redirect to a signed S3 URL or stream locally.
     */
    public function download(
        Tenant $tenant,
        UploadedDocument $uploadedDocument,
        LogAuditActivity $logAuditActivity,
        ResolveDocumentTemporaryUrl $resolveDocumentTemporaryUrl,
    ): RedirectResponse|StreamedResponse {
        $this->authorize('download', $uploadedDocument);

        $logAuditActivity->handle(
            AuditEvent::DocumentDownloaded,
            $uploadedDocument,
            ['original_filename' => $uploadedDocument->original_filename],
        );

        // POC: private MinIO/S3 objects are served via short-lived signed URLs.
        if ($resolveDocumentTemporaryUrl->supportsTemporaryUrl($uploadedDocument)) {
            $url = $resolveDocumentTemporaryUrl->handle(
                $uploadedDocument,
                now()->addMinutes(5),
            );

            return redirect()->away($url);
        }

        // Local/CI disks keep streaming through Laravel (Storage::fake friendly).
        return Storage::disk($uploadedDocument->disk)->download(
            $uploadedDocument->path,
            $uploadedDocument->original_filename,
        );
    }
}
