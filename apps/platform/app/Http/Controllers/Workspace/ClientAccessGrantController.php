<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Actions\Workspace\CreateClientAccessGrant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreClientAccessGrantRequest;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class ClientAccessGrantController extends Controller
{
    public function store(
        StoreClientAccessGrantRequest $request,
        Dossier $dossier,
        CreateClientAccessGrant $createClientAccessGrant,
    ): RedirectResponse {
        $this->authorize('view', $dossier);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $result = $createClientAccessGrant(
            $dossier,
            $user,
            (int) ($request->validated('expires_in_days') ?? 7),
        );

        // Flash plaintext once — the DB only stores the hash.
        session()->flash('access_grant_token', $result['plain_text_token']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Client access token created. Copy it now — it will not be shown again.',
        ]);

        return to_route('workspaces.dossiers.show', $dossier);
    }

    public function destroy(ClientAccessGrant $grant): RedirectResponse
    {
        $this->authorize('revoke', $grant);

        if ($grant->revoked_at === null) {
            $grant->update(['revoked_at' => now()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Client access grant revoked.',
        ]);

        return to_route('workspaces.dossiers.show', $grant->dossier_id);
    }
}
