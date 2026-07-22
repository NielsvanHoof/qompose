<?php

declare(strict_types=1);

namespace App\Policies\Dossiers;

use App\Enums\DossierStatus;
use App\Enums\Permission;
use App\Models\Dossier;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantPermission;

final class DossierPolicy
{
    use AuthorizesTenantPermission;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewDossiers->value);
    }

    public function view(User $user, Dossier $dossier): bool
    {
        return $this->userHasPermissionInTenant(
            $user,
            $dossier->tenant,
            Permission::ViewDossiers,
        );
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CreateDossiers->value);
    }

    public function update(User $user, Dossier $dossier): bool
    {
        return $this->userHasPermissionInTenant($user, $dossier->tenant, Permission::CreateDossiers)
            && $dossier->status !== DossierStatus::Completed;
    }

    public function complete(User $user, Dossier $dossier): bool
    {
        return $this->userHasPermissionInTenant($user, $dossier->tenant, Permission::ReviewDocuments)
            && $dossier->status !== DossierStatus::Completed;
    }

    public function delete(User $user, Dossier $dossier): bool
    {
        return $this->userHasPermissionInTenant($user, $dossier->tenant, Permission::CreateDossiers);
    }
}
