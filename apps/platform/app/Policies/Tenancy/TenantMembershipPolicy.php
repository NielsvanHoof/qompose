<?php

declare(strict_types=1);

namespace App\Policies\Tenancy;

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantPermission;
use App\Support\Tenancy\WorkspaceMemberRoles;

final class TenantMembershipPolicy
{
    use AuthorizesTenantPermission;

    public function __construct(private readonly WorkspaceMemberRoles $memberRoles) {}

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageMembers->value);
    }

    public function update(User $user, TenantMembership $membership): bool
    {
        $membership->loadMissing('tenant');
        $tenant = $membership->tenant;

        if (! $tenant instanceof Tenant) {
            return false;
        }

        return $this->userHasPermissionInTenant(
            $user,
            $tenant,
            Permission::ManageMembers,
        );
    }

    public function suspend(User $user, TenantMembership $membership): bool
    {
        if (! $this->update($user, $membership)) {
            return false;
        }

        if ($membership->user_id === $user->id) {
            return false;
        }

        return $membership->status === TenantMembershipStatus::Active;
    }

    public function remove(User $user, TenantMembership $membership): bool
    {
        if (! $this->update($user, $membership)) {
            return false;
        }

        $membership->loadMissing(['tenant', 'user']);
        $tenant = $membership->tenant;

        if (! $tenant instanceof Tenant) {
            return false;
        }

        $member = $membership->user;

        if (! $member instanceof User) {
            return false;
        }

        // Self-removal is allowed only when another owner remains.
        if ($membership->user_id === $user->id) {
            return ! $this->memberRoles->isLastActiveOwner($member, $tenant);
        }

        return true;
    }

    public function assignRole(User $user, TenantMembership $membership, Role $role): bool
    {
        if (! $this->update($user, $membership)) {
            return false;
        }

        $membership->loadMissing('tenant');
        $tenant = $membership->tenant;

        if (! $tenant instanceof Tenant) {
            return false;
        }

        return $this->memberRoles->actorCanAssignRole($user, $tenant, $role);
    }
}
