<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Enums\Role as RoleEnum;
use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;
use Throwable;

use function getPermissionsTeamId;
use function setPermissionsTeamId;

final class ProvisionTenant
{
    public function handle(
        string $name,
        User $owner,
        ?string $slug = null,
        RoleEnum $ownerRole = RoleEnum::Owner,
    ): Tenant {
        $previousPermissionsTeamId = getPermissionsTeamId();

        try {
            return DB::transaction(function () use ($name, $owner, $slug, $ownerRole): Tenant {
                $tenant = $this->createTenantWithAvailableSlug($name, $slug);

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
            });
        } catch (Throwable $exception) {
            setPermissionsTeamId($previousPermissionsTeamId);
            $owner->unsetRelation('roles');
            $owner->unsetRelation('permissions');

            throw $exception;
        }
    }

    private function createTenantWithAvailableSlug(string $name, ?string $requestedSlug): Tenant
    {
        $baseSlug = Str::slug($requestedSlug ?? $name);

        if ($baseSlug === '') {
            $baseSlug = 'workspace';
        }

        $suffix = 1;

        while (true) {
            $slug = $this->slugWithSuffix($baseSlug, $suffix);

            if (Tenant::query()->where('slug', $slug)->toBase()->exists()) {
                $suffix++;

                continue;
            }

            try {
                return DB::transaction(fn (): Tenant => Tenant::query()->create([
                    'name' => $name,
                    'slug' => $slug,
                ]));
            } catch (UniqueConstraintViolationException) {
                $suffix++;
            }
        }
    }

    private function slugWithSuffix(string $baseSlug, int $suffix): string
    {
        if ($suffix === 1) {
            return Str::limit($baseSlug, 255, '');
        }

        $suffixValue = "-$suffix";

        return Str::limit($baseSlug, 255 - mb_strlen($suffixValue), '').$suffixValue;
    }

    private function seedRolesForTenant(Tenant $tenant): void
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
