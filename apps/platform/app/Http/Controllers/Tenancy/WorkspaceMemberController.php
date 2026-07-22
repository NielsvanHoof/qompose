<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Actions\Tenancy\InviteTenantMemberAction;
use App\Actions\Tenancy\RemoveTenantMemberAction;
use App\Actions\Tenancy\SuspendTenantMemberAction;
use App\Actions\Tenancy\UpdateTenantMemberRoleAction;
use App\Enums\TenantMembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\InviteTenantMemberRequest;
use App\Http\Requests\Tenancy\UpdateTenantMemberRequest;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Queries\Tenancy\FetchWorkspaceMembersQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WorkspaceMemberController extends Controller
{
    public function index(
        Tenant $tenant,
        Request $request,
        FetchWorkspaceMembersQuery $fetchWorkspaceMembers,
    ): Response {
        $this->authorize('viewAny', TenantMembership::class);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return Inertia::render(
            'workspaces/members/index',
            $fetchWorkspaceMembers->handle($tenant, $user),
        );
    }

    public function store(
        Tenant $tenant,
        InviteTenantMemberRequest $request,
        InviteTenantMemberAction $inviteTenantMember,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $inviteTenantMember->handle(
            $tenant,
            $user,
            $request->email(),
            $request->role(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invitation sent.'),
        ]);

        return to_route('workspaces.members.index', $this->workspaceRouteParameters());
    }

    public function update(
        Tenant $tenant,
        TenantMembership $membership,
        UpdateTenantMemberRequest $request,
        UpdateTenantMemberRoleAction $updateTenantMemberRole,
        SuspendTenantMemberAction $suspendTenantMember,
    ): RedirectResponse {
        $this->ensureMembershipBelongsToTenant($membership, $tenant);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $role = $request->role();
        $status = $request->status();

        if ($role instanceof \App\Enums\Role) {
            $updateTenantMemberRole->handle($membership, $user, $role);
        }

        if ($status === TenantMembershipStatus::Suspended) {
            $this->authorize('suspend', $membership);
            $suspendTenantMember->handle($membership, $user);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Member updated.'),
        ]);

        return to_route('workspaces.members.index', $this->workspaceRouteParameters());
    }

    public function destroy(
        Tenant $tenant,
        TenantMembership $membership,
        Request $request,
        RemoveTenantMemberAction $removeTenantMember,
    ): RedirectResponse {
        $this->ensureMembershipBelongsToTenant($membership, $tenant);
        $this->authorize('remove', $membership);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $removeTenantMember->handle($membership, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Member removed.'),
        ]);

        return to_route('workspaces.members.index', $this->workspaceRouteParameters());
    }

    private function ensureMembershipBelongsToTenant(TenantMembership $membership, Tenant $tenant): void
    {
        if ($membership->tenant_id !== $tenant->id) {
            abort(404);
        }
    }
}
