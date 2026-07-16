<?php

declare(strict_types=1);

namespace App\Policies\Portal;

use App\Enums\Permission;
use App\Models\ClientAccessGrant;
use App\Models\Tenant;
use App\Models\User;

final class ClientAccessGrantPolicy
{
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateDossiers->value);
    }

    public function revoke(User $user, ClientAccessGrant $grant): bool
    {
        $tenant = $grant->tenant;

        return $user->can(Permission::CreateDossiers->value)
            && $tenant instanceof Tenant
            && $user->belongsToTenant($tenant);
    }
}
