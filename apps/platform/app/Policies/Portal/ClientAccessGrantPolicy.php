<?php

declare(strict_types=1);

namespace App\Policies\Portal;

use App\Enums\Permission;
use App\Models\ClientAccessGrant;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantPermission;

final class ClientAccessGrantPolicy
{
    use AuthorizesTenantPermission;

    public function create(User $user): bool
    {
        return $user->can(Permission::CreateDossiers->value);
    }

    public function revoke(User $user, ClientAccessGrant $grant): bool
    {
        return $this->userHasPermissionInTenant(
            $user,
            $grant->tenant,
            Permission::CreateDossiers,
        );
    }
}
