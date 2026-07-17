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
use Inertia\Response;
use JsonException;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function is_array;
use function is_string;
use function json_decode;

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
     * Dedicated OCR extraction page — key/values and tables off the dossier list.
     */
    public function show(Tenant $tenant, UploadedDocument $uploadedDocument): Response
    {
        $this->authorize('view', $uploadedDocument);

        $uploadedDocument->load([
            'documentRequest:id,dossier_id,title,tenant_id',
            'documentRequest.dossier:id,title,tenant_id',
        ]);

        $documentRequest = $uploadedDocument->documentRequest;
        $dossier = $documentRequest?->dossier;

        return Inertia::render('uploaded-documents/show', [
            'uploaded_document' => [
                'id' => $uploadedDocument->id,
                'original_filename' => $uploadedDocument->original_filename,
                'size_bytes' => $uploadedDocument->size_bytes,
                'uploaded_at' => $uploadedDocument->uploaded_at->toIso8601String(),
                'processing_status' => $uploadedDocument->processing_status->value,
                'processing_error' => $uploadedDocument->processing_error,
                'extraction' => $this->parseExtraction($uploadedDocument->extracted_text),
                'raw_json' => $uploadedDocument->extracted_text,
            ],
            'document_request' => $documentRequest === null ? null : [
                'id' => $documentRequest->id,
                'title' => $documentRequest->title,
            ],
            'dossier' => $dossier === null ? null : [
                'id' => $dossier->id,
                'title' => $dossier->title,
            ],
            'can_download' => request()->user()?->can('download', $uploadedDocument) ?? false,
        ]);
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

    /**
     * Decode stored AnalyzeDocument JSON into a typed extraction payload for Inertia.
     *
     * Trusts our own OCR-written shape; only guards against missing/invalid JSON.
     *
     * @return array{key_values: array<string, string|list<string>>, tables: list<list<list<string>>>}|null
     */
    private function parseExtraction(?string $raw): ?array
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $keyValues = $decoded['key_values'] ?? [];
        $tables = $decoded['tables'] ?? [];

        if (! is_array($keyValues) || ! is_array($tables)) {
            return null;
        }

        /** @var array{key_values: array<string, string|list<string>>, tables: list<list<list<string>>>} $payload */
        $payload = [
            'key_values' => $keyValues,
            'tables' => array_values($tables),
        ];

        return $payload;
    }
}
