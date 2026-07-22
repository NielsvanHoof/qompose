<?php

declare(strict_types=1);

namespace App\Queries\Tenancy;

use App\Data\Tenancy\WorkspaceInvitationData;
use App\Data\Tenancy\WorkspaceMemberData;
use App\Data\Tenancy\WorkspaceMembersPageData;
use App\Data\Tenancy\WorkspaceRoleOptionData;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\WorkspaceMemberRoles;
use Illuminate\Database\Eloquent\Collection;

final class FetchWorkspaceMembersQuery
{
    public function __construct(private readonly WorkspaceMemberRoles $memberRoles) {}

    public function handle(Tenant $tenant, User $viewer): WorkspaceMembersPageData
    {
        $viewerIsOwner = $this->memberRoles->roleFor($viewer, $tenant) === Role::Owner;

        $membershipsQuery = TenantMembership::query()
            ->with('user')
            ->where('tenant_id', $tenant->id);

        // Prefer the base query builder for whereIn/orderBy to satisfy strict-rules.
        $membershipsQuery->getQuery()
            ->whereIn('status', [
                TenantMembershipStatus::Active->value,
                TenantMembershipStatus::Suspended->value,
            ])
            ->orderBy('joined_at');

        /** @var Collection<int, TenantMembership> $memberships */
        $memberships = $membershipsQuery->get();

        /** @var list<WorkspaceMemberData> $members */
        $members = [];

        foreach ($memberships as $membership) {
            $user = $membership->user;

            if (! $user instanceof User) {
                continue;
            }

            $role = $this->memberRoles->roleFor($user, $tenant);

            $members[] = new WorkspaceMemberData(
                id: $membership->id,
                userId: $user->id,
                name: $user->name,
                email: $user->email,
                status: $membership->status->value,
                role: $role?->value,
                roleLabel: $role?->label(),
                joinedAt: $membership->joined_at?->toIso8601String(),
                lastAccessedAt: $membership->last_accessed_at?->toIso8601String(),
                isCurrentUser: $user->id === $viewer->id,
            );
        }

        $invitationsQuery = TenantInvitation::query()
            ->where('tenant_id', $tenant->id);

        TenantInvitation::constrainToPending($invitationsQuery);
        $invitationsQuery->getQuery()->orderByDesc('created_at');

        /** @var Collection<int, TenantInvitation> $invitationModels */
        $invitationModels = $invitationsQuery->get();

        /** @var list<WorkspaceInvitationData> $invitations */
        $invitations = [];

        foreach ($invitationModels as $invitation) {
            $invitations[] = new WorkspaceInvitationData(
                id: $invitation->id,
                email: $invitation->email,
                role: $invitation->role->value,
                roleLabel: $invitation->role->label(),
                invitedAt: $invitation->created_at?->toIso8601String() ?? now()->toIso8601String(),
                expiresAt: $invitation->expires_at->toIso8601String(),
            );
        }

        /** @var list<WorkspaceRoleOptionData> $roleOptions */
        $roleOptions = [];

        foreach (Role::assignable() as $role) {
            if (! $viewerIsOwner && $role === Role::Owner) {
                continue;
            }

            $roleOptions[] = new WorkspaceRoleOptionData(
                value: $role->value,
                label: $role->label(),
            );
        }

        return new WorkspaceMembersPageData(
            members: $members,
            invitations: $invitations,
            roleOptions: $roleOptions,
            canAssignOwner: $viewerIsOwner,
        );
    }
}
