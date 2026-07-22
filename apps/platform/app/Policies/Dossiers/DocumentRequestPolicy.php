<?php

declare(strict_types=1);

namespace App\Policies\Dossiers;

use App\Enums\DossierStatus;
use App\Enums\Permission;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantPermission;

final class DocumentRequestPolicy
{
    use AuthorizesTenantPermission;

    /**
     * Media library and any future document-request index use the same gate as dossiers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewDossiers->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CreateDossiers->value);
    }

    public function view(User $user, DocumentRequest $documentRequest): bool
    {
        return $this->userHasPermissionInTenant(
            $user,
            $documentRequest->tenant,
            Permission::ViewDossiers,
        );
    }

    /**
     * Staff fallback upload onto a request (e.g. files received by email or walk-in).
     * Clients remain the primary upload path via the portal.
     */
    public function upload(User $user, DocumentRequest $documentRequest): bool
    {
        return $this->canActOnOpenDossier($user, $documentRequest, Permission::CreateDossiers);
    }

    public function update(User $user, DocumentRequest $documentRequest): bool
    {
        return $this->upload($user, $documentRequest);
    }

    public function delete(User $user, DocumentRequest $documentRequest): bool
    {
        return $this->upload($user, $documentRequest);
    }

    public function review(User $user, DocumentRequest $documentRequest): bool
    {
        return $this->canActOnOpenDossier($user, $documentRequest, Permission::ReviewDocuments);
    }

    private function canActOnOpenDossier(
        User $user,
        DocumentRequest $documentRequest,
        Permission $permission,
    ): bool {
        $dossier = $documentRequest->dossier;

        return $this->userHasPermissionInTenant($user, $documentRequest->tenant, $permission)
            && $dossier instanceof Dossier
            && $dossier->status !== DossierStatus::Completed;
    }
}
