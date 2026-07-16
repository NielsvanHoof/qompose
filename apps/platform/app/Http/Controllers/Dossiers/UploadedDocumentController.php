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
    ): RedirectResponse {
        // Ensure the request belongs to this dossier (tenant scope already applied).
        abort_unless($documentRequest->dossier_id === $dossier->id, 404);

        $this->authorize('view', $dossier);

        $file = $request->file('document');

        if ($file === null) {
            return back()->withErrors(['document' => 'A document file is required.']);
        }

        $uploadedDocument = $uploadDocumentForRequest($documentRequest, $file);

        app(LogAuditActivity::class)(
            AuditEvent::DocumentUploaded,
            $uploadedDocument,
            ['original_filename' => $uploadedDocument->original_filename],
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

    public function download(Tenant $tenant, UploadedDocument $uploadedDocument): StreamedResponse
    {
        $this->authorize('download', $uploadedDocument);

        app(LogAuditActivity::class)(
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
