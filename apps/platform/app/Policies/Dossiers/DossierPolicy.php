<?php

declare(strict_types=1);

namespace App\Policies\Dossiers;

use App\Enums\DossierStatus;
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

    public function update(User $user, Dossier $dossier): bool
    {
        return $this->belongsToTenantWithPermission($user, $dossier, Permission::CreateDossiers)
            && $dossier->status !== DossierStatus::Completed;
    }

    public function complete(User $user, Dossier $dossier): bool
    {
        return $this->belongsToTenantWithPermission($user, $dossier, Permission::ReviewDocuments)
            && $dossier->status !== DossierStatus::Completed;
    }

    private function belongsToTenantWithPermission(
        User $user,
        Dossier $dossier,
        Permission $permission,
    ): bool {
        $tenant = $dossier->tenant;

        return $user->can($permission->value)
            && $tenant instanceof Tenant
            && $user->belongsToTenant($tenant);
    }
}
