<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Audit\LogAuditActivityAction;
use App\Actions\Dossiers\DocumentRequests\ResolveDocumentTemporaryUrlAction;
use App\Actions\Dossiers\DocumentRequests\UploadStaffDocumentAction;
use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\DocumentRequests\StoreUploadedDocumentRequest;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use App\Queries\Dossiers\FetchUploadedDocumentShowQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Staff upload + OCR extraction page + document download.
 */
final class UploadedDocumentController extends Controller
{
    public function store(
        Tenant $tenant,
        StoreUploadedDocumentRequest $request,
        Dossier $dossier,
        DocumentRequest $documentRequest,
        UploadStaffDocumentAction $uploadStaffDocument,
    ): RedirectResponse {
        // Parent ownership is enforced by scoped route bindings
        // (dossier → documentRequest). Tenant scope is already applied.
        $this->authorize('view', $dossier);

        $file = $request->file('document');

        if ($file === null) {
            return back()->withErrors(['document' => __('A document file is required.')]);
        }

        $uploadStaffDocument->handle($documentRequest, $file);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Document uploaded.'),
        ]);

        // Staff uploads land on the focused review page.
        return to_route(
            'workspaces.dossiers.review',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }

    /**
     * Dedicated OCR extraction page — key/values and tables off the dossier list.
     */
    public function show(
        Tenant $tenant,
        UploadedDocument $uploadedDocument,
        FetchUploadedDocumentShowQuery $fetchUploadedDocumentShow,
    ): Response {
        $this->authorize('view', $uploadedDocument);

        return Inertia::render(
            'uploaded-documents/show',
            $fetchUploadedDocumentShow->handle($uploadedDocument),
        );
    }

    /**
     * Authorize, audit, then either redirect to a signed S3 URL or stream locally.
     */
    public function download(
        Tenant $tenant,
        UploadedDocument $uploadedDocument,
        LogAuditActivityAction $logAuditActivity,
        ResolveDocumentTemporaryUrlAction $resolveDocumentTemporaryUrl,
    ): RedirectResponse|StreamedResponse {
        $this->authorize('download', $uploadedDocument);

        $logAuditActivity->handle(
            AuditEvent::DocumentDownloaded,
            $uploadedDocument,
            ['original_filename' => $uploadedDocument->original_filename],
        );

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
