<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Audit\LogAuditActivityAction;
use App\Actions\Dossiers\ResolveDocumentTemporaryUrlAction;
use App\Actions\Dossiers\UploadStaffDocumentAction;
use App\Contracts\Ocr\StructuresDocumentText;
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
use Inertia\Response;
use JsonException;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function is_array;
use function is_string;
use function json_decode;

/**
 * @phpstan-import-type DocumentExtractionPayload from StructuresDocumentText
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

    /**
     * Decode stored Bedrock-structured OCR JSON into a typed extraction payload for Inertia.
     *
     * Trusts our own OCR-written shape; only guards against missing/invalid JSON.
     *
     * @return DocumentExtractionPayload|null
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

        $fields = $decoded['fields'] ?? [];
        $tables = $decoded['tables'] ?? [];
        $notes = $decoded['notes'] ?? [];

        if (! is_array($fields) || ! is_array($tables) || ! is_array($notes)) {
            return null;
        }

        $documentType = $decoded['document_type'] ?? null;
        $summary = $decoded['summary'] ?? null;

        /** @var DocumentExtractionPayload $payload */
        $payload = [
            'document_type' => is_string($documentType) ? $documentType : null,
            'summary' => is_string($summary) ? $summary : null,
            'fields' => array_values($fields),
            'tables' => array_values($tables),
            'notes' => array_values($notes),
        ];

        return $payload;
    }
}
