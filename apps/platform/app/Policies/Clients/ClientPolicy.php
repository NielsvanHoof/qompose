<?php

declare(strict_types=1);

namespace App\Policies\Clients;

use App\Enums\Permission;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;

final class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageClients->value);
    }

    public function view(User $user, Client $client): bool
    {
        $tenant = $client->tenant;

        return $user->can(Permission::ManageClients->value)
            && $tenant instanceof Tenant
            && $user->belongsToTenant($tenant);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageClients->value);
    }
}
