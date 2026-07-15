<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ClientPortalController extends Controller
{
    /**
     * Show the guest document portal for a valid access grant.
     */
    public function show(Request $request, string $token): Response
    {
        $grant = $this->grantFromRequest($request);

        // Touch last_used_at without changing updated_at semantics beyond the grant itself.
        $grant->forceFill(['last_used_at' => now()])->save();

        $dossier = Dossier::query()
            ->with([
                'client:id,name,email',
                'tenant:id,name',
                'documentRequests' => fn ($query) => $query
                    ->oldest('sort_order')
                    ->oldest('id')
                    ->with('uploadedDocument:id,document_request_id,original_filename,size_bytes,uploaded_at'),
            ])
            ->findOrFail($grant->dossier_id);

        $tenant = $dossier->tenant;
        $client = $dossier->client;

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        if (! $client instanceof Client) {
            abort(404);
        }

        return Inertia::render('portal/show', [
            'token' => $token,
            'firm' => [
                'name' => $tenant->name,
            ],
            'dossier' => [
                'title' => $dossier->title,
                'reference' => $dossier->reference,
                'client' => [
                    'name' => $client->name,
                ],
                'expires_at' => $grant->expires_at->toIso8601String(),
                'document_requests' => $dossier->documentRequests->map(fn ($documentRequest): array => [
                    'id' => $documentRequest->id,
                    'type' => $documentRequest->type->value,
                    'title' => $documentRequest->title,
                    'instructions' => $documentRequest->instructions,
                    'status' => $documentRequest->status->value,
                    'answer_text' => $documentRequest->answer_text,
                    'answer_boolean' => $documentRequest->answer_boolean,
                    'uploaded_document' => $documentRequest->uploadedDocument === null
                        ? null
                        : [
                            'original_filename' => $documentRequest->uploadedDocument->original_filename,
                            'size_bytes' => $documentRequest->uploadedDocument->size_bytes,
                            'uploaded_at' => $documentRequest->uploadedDocument->uploaded_at->toIso8601String(),
                        ],
                ]),
            ],
        ]);
    }

    private function grantFromRequest(Request $request): ClientAccessGrant
    {
        $grant = $request->attributes->get(ResolveClientPortalGrant::REQUEST_ATTRIBUTE);

        if (! $grant instanceof ClientAccessGrant) {
            abort(404);
        }

        return $grant;
    }
}
