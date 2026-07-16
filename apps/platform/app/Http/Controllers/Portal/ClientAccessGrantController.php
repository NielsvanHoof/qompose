<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\CreateClientAccessGrant;
use App\Actions\Portal\SendClientPortalInvite;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreClientAccessGrantRequest;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class ClientAccessGrantController extends Controller
{
    public function store(
        StoreClientAccessGrantRequest $request,
        Dossier $dossier,
        CreateClientAccessGrant $createClientAccessGrant,
        SendClientPortalInvite $sendClientPortalInvite,
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

        $plainTextToken = $result['plain_text_token'];
        $portalUrl = URL::route('portal.show', ['token' => $plainTextToken]);

        // Flash plaintext once — the DB only stores the hash.
        $request->session()->flash('access_grant_token', $plainTextToken);
        $request->session()->flash('access_grant_portal_url', $portalUrl);

        $sendInvite = (bool) ($request->validated('send_invite') ?? true);

        if ($sendInvite) {
            $sendClientPortalInvite($dossier, $result['grant'], $plainTextToken);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Client invite emailed. Copy the portal link now if you need it again.',
            ]);
        } else {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Client access link created. Copy it now — it will not be shown again.',
            ]);
        }

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
