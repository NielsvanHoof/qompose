<?php

declare(strict_types=1);

namespace App\Policies\Clients;

use App\Enums\Permission;
use App\Models\Client;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantPermission;

final class ClientPolicy
{
    use AuthorizesTenantPermission;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageClients->value);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->userHasPermissionInTenant(
            $user,
            $client->tenant,
            Permission::ManageClients,
        );
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageClients->value);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->userHasPermissionInTenant(
            $user,
            $client->tenant,
            Permission::ManageClients,
        );
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->userHasPermissionInTenant(
            $user,
            $client->tenant,
            Permission::ManageClients,
        );
    }

    public function restore(User $user, Client $client): bool
    {
        return $this->userHasPermissionInTenant(
            $user,
            $client->tenant,
            Permission::ManageClients,
        );
    }
}
