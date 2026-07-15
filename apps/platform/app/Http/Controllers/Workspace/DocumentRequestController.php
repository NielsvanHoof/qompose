<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreDocumentRequestRequest;
use App\Models\Dossier;
use Illuminate\Http\RedirectResponse;

final class DocumentRequestController extends Controller
{
    public function store(
        StoreDocumentRequestRequest $request,
        Dossier $dossier,
    ): RedirectResponse {
        // Authorization lives on StoreDocumentRequestRequest (view dossier + create).
        $documentRequest = $dossier->documentRequests()->create($request->validated());

        app(LogAuditActivity::class)(
            AuditEvent::DocumentRequestCreated,
            $documentRequest,
        );

        return to_route('workspaces.dossiers.show', $dossier);
    }
}
