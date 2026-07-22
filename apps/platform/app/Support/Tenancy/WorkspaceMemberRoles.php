<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

use function getPermissionsTeamId;
use function is_string;
use function setPermissionsTeamId;

/**
 * Shared helpers for workspace member role checks (last owner, assignable roles).
 */
final class WorkspaceMemberRoles
{
    public function roleFor(User $user, Tenant $tenant): ?Role
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($tenant->id);

        try {
            $roleName = $user->getRoleNames()->first();

            return is_string($roleName) ? Role::tryFrom($roleName) : null;
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    public function activeOwnerCount(Tenant $tenant): int
    {
        $teamForeignKey = (string) config('permission.column_names.team_foreign_key');
        $rolesTable = (string) config('permission.table_names.roles');
        $modelHasRolesTable = (string) config('permission.table_names.model_has_roles');
        $modelKey = (string) config('permission.column_names.model_morph_key');

        // Count via joins instead of Spatie's role() scope — Attribute/macro
        // scopes trip phpstan-strict-rules (staticMethod.dynamicCall).
        return DB::table('tenant_memberships')
            ->join($modelHasRolesTable, function ($join) use ($tenant, $teamForeignKey, $modelHasRolesTable, $modelKey): void {
                $join->on('tenant_memberships.user_id', '=', "{$modelHasRolesTable}.{$modelKey}")
                    ->where("{$modelHasRolesTable}.model_type", User::class)
                    ->where("{$modelHasRolesTable}.{$teamForeignKey}", $tenant->id);
            })
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$modelHasRolesTable}.role_id")
            ->where('tenant_memberships.tenant_id', $tenant->id)
            ->where('tenant_memberships.status', TenantMembershipStatus::Active->value)
            ->where("{$rolesTable}.name", Role::Owner->value)
            ->distinct()
            ->count('tenant_memberships.user_id');
    }

    public function isLastActiveOwner(User $user, Tenant $tenant): bool
    {
        $role = $this->roleFor($user, $tenant);

        if ($role !== Role::Owner) {
            return false;
        }

        return $this->activeOwnerCount($tenant) <= 1;
    }

    public function actorCanAssignRole(User $actor, Tenant $tenant, Role $role): bool
    {
        if ($role === Role::Owner) {
            return $this->roleFor($actor, $tenant) === Role::Owner;
        }

        return true;
    }

    /**
     * Assign a single tenant-scoped role, replacing any previous roles for the team.
     */
    public function syncRole(User $user, Tenant $tenant, Role $role): void
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($tenant->id);

        try {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $user->syncRoles([$role->value]);
        } finally {
            setPermissionsTeamId($previousTeamId);
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }
    }

    /**
     * Remove all Spatie roles for this user within the tenant team.
     */
    public function clearRoles(User $user, Tenant $tenant): void
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($tenant->id);

        try {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $user->syncRoles([]);
        } finally {
            setPermissionsTeamId($previousTeamId);
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }
    }

    public function membershipBelongsToTenant(TenantMembership $membership, Tenant $tenant): bool
    {
        return $membership->tenant_id === $tenant->id;
    }

    public function requireTenant(TenantMembership $membership): Tenant
    {
        $tenant = $membership->tenant;

        if (! $tenant instanceof Tenant) {
            $membership->loadMissing('tenant');
            $tenant = $membership->tenant;
        }

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('Membership is missing its tenant.');
        }

        return $tenant;
    }

    /**
     * Ensure no concurrent accept creates a duplicate membership.
     */
    public function lockMembershipPair(int $tenantId, int $userId): void
    {
        DB::table('tenant_memberships')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->exists();
    }
}
