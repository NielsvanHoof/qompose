<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Actions\Tenancy\ResendTenantInvitationAction;
use App\Actions\Tenancy\RevokeTenantInvitationAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class TenantInvitationController extends Controller
{
    public function resend(
        Tenant $tenant,
        TenantInvitation $invitation,
        ResendTenantInvitationAction $resendTenantInvitation,
    ): RedirectResponse {
        $this->ensureInvitationBelongsToTenant($invitation, $tenant);
        $this->authorize('resend', $invitation);

        $resendTenantInvitation->handle($invitation);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invitation resent.'),
        ]);

        return to_route('workspaces.members.index', $this->workspaceRouteParameters());
    }

    public function destroy(
        Tenant $tenant,
        TenantInvitation $invitation,
        Request $request,
        RevokeTenantInvitationAction $revokeTenantInvitation,
    ): RedirectResponse {
        $this->ensureInvitationBelongsToTenant($invitation, $tenant);
        $this->authorize('revoke', $invitation);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $revokeTenantInvitation->handle($invitation, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invitation revoked.'),
        ]);

        return to_route('workspaces.members.index', $this->workspaceRouteParameters());
    }

    private function ensureInvitationBelongsToTenant(TenantInvitation $invitation, Tenant $tenant): void
    {
        if ($invitation->tenant_id !== $tenant->id) {
            abort(404);
        }
    }
}
