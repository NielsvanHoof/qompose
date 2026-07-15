<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\Enums\Role as RoleEnum;
use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;

use function setPermissionsTeamId;

final class ProvisionTenant
{
    public function __invoke(
        string $name,
        User $owner,
        ?string $slug = null,
        RoleEnum $ownerRole = RoleEnum::Owner,
    ): Tenant {
        $tenant = Tenant::query()->create([
            'name' => $name,
            'slug' => $slug ?? Str::slug($name),
        ]);

        $this->seedRolesForTenant($tenant);

        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'status' => TenantMembershipStatus::Active,
            'joined_at' => now(),
            'last_accessed_at' => now(),
        ]);

        setPermissionsTeamId($tenant->id);
        $owner->unsetRelation('roles');
        $owner->unsetRelation('permissions');
        $owner->assignRole($ownerRole->value);

        return $tenant;
    }

    public function seedRolesForTenant(Tenant $tenant): void
    {
        setPermissionsTeamId($tenant->id);

        foreach (RoleEnum::cases() as $roleEnum) {
            $role = RoleModel::query()->create([
                'name' => $roleEnum->value,
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            $role->givePermissionTo(
                collect($roleEnum->permissions())
                    ->map(fn ($permission) => $permission->value)
                    ->all(),
            );
        }
    }
}
