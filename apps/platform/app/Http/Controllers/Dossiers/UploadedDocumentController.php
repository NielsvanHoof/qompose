<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Audit\LogAuditActivity;
use App\Actions\Dossiers\UploadDocumentForRequest;
use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\StoreUploadedDocumentRequest;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\UploadedDocument;
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
    ): RedirectResponse {
        // Parent ownership is enforced by scoped route bindings
        // (dossier → documentRequest). Tenant scope is already applied.
        $this->authorize('view', $dossier);

        $file = $request->file('document');

        if ($file === null) {
            return back()->withErrors(['document' => 'A document file is required.']);
        }

        $uploadedDocument = $uploadDocumentForRequest->handle(
            $documentRequest,
            $file,
            static function (UploadedDocument $uploadedDocument) use ($logAuditActivity): void {
                $logAuditActivity->handle(
                    AuditEvent::DocumentUploaded,
                    $uploadedDocument,
                    ['original_filename' => $uploadedDocument->original_filename],
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

    public function download(
        Tenant $tenant,
        UploadedDocument $uploadedDocument,
        LogAuditActivity $logAuditActivity,
    ): StreamedResponse {
        $this->authorize('download', $uploadedDocument);

        $logAuditActivity->handle(
            AuditEvent::DocumentDownloaded,
            $uploadedDocument,
            ['original_filename' => $uploadedDocument->original_filename],
        );

        return Storage::disk($uploadedDocument->disk)->download(
            $uploadedDocument->path,
            $uploadedDocument->original_filename,
        );
    }
}
