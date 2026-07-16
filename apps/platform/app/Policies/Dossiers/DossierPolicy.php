<?php

declare(strict_types=1);

namespace App\Policies\Dossiers;

use App\Enums\Permission;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;

final class DossierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewDossiers->value);
    }

    public function view(User $user, Dossier $dossier): bool
    {
        $tenant = $dossier->tenant;

        return $user->can(Permission::ViewDossiers->value)
            && $tenant instanceof Tenant
            && $user->belongsToTenant($tenant);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CreateDossiers->value);
    }
}
