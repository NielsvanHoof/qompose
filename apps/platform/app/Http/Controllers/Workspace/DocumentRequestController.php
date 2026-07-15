<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;

final class DocumentRequestController extends Controller
{
    public function store(
        StoreDocumentRequestRequest $request,
        Tenant $tenant,
        int $dossier,
    ): RedirectResponse {
        $record = Dossier::query()->whereKey($dossier)->firstOrFail();

        $this->authorize('view', $record);
        $this->authorize('create', DocumentRequest::class);

        $documentRequest = $record->documentRequests()->create($request->validated());

        app(LogAuditActivity::class)(
            AuditEvent::DocumentRequestCreated,
            $documentRequest,
        );

        return to_route('workspaces.dossiers.show', [$tenant, $record->id]);
    }
}
