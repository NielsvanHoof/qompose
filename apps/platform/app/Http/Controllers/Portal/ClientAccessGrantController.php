<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\IssueClientPortalAccess;
use App\Actions\Portal\RevokeClientPortalAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreClientAccessGrantRequest;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class ClientAccessGrantController extends Controller
{
    public function store(
        Tenant $tenant,
        StoreClientAccessGrantRequest $request,
        Dossier $dossier,
        IssueClientPortalAccess $issueClientPortalAccess,
    ): RedirectResponse {
        $this->authorize('view', $dossier);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $sendInvite = (bool) ($request->validated('send_invite') ?? true);

        $result = $issueClientPortalAccess->handle(
            $dossier,
            $user,
            (int) ($request->validated('expires_in_days') ?? 7),
            $sendInvite,
        );

        $plainTextToken = $result['plain_text_token'];
        $portalUrl = URL::route('portal.show', ['token' => $plainTextToken]);

        // Flash plaintext once — the DB only stores the hash.
        $request->session()->flash('access_grant_token', $plainTextToken);
        $request->session()->flash('access_grant_portal_url', $portalUrl);

        if ($sendInvite) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Client invite queued. Copy the portal link now if you need it again.',
            ]);
        } else {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Client access link created. Copy it now — it will not be shown again.',
            ]);
        }

        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }

    public function destroy(
        Tenant $tenant,
        ClientAccessGrant $grant,
        RevokeClientPortalAccess $revokeClientPortalAccess,
    ): RedirectResponse {
        $this->authorize('revoke', $grant);

        $user = request()->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $revokeClientPortalAccess->handle($grant, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Client access grant revoked.',
        ]);

        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $grant->dossier_id]),
        );
    }
}
