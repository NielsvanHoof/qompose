<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;

trait AuthorizesTenantPermission
{
    /**
     * Whether the user holds a permission and belongs to the given tenant.
     */
    protected function userHasPermissionInTenant(
        User $user,
        ?Tenant $tenant,
        Permission $permission,
    ): bool {
        return $user->can($permission->value)
            && $tenant instanceof Tenant
            && $user->belongsToTenant($tenant);
    }
}
