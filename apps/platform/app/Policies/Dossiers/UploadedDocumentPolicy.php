<?php

declare(strict_types=1);

namespace App\Policies\Dossiers;

use App\Enums\Permission;
use App\Models\UploadedDocument;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantPermission;

final class UploadedDocumentPolicy
{
    use AuthorizesTenantPermission;

    /**
     * Staff can open the OCR extraction page when they can view dossiers.
     */
    public function view(User $user, UploadedDocument $uploadedDocument): bool
    {
        return $this->userHasPermissionInTenant(
            $user,
            $uploadedDocument->tenant,
            Permission::ViewDossiers,
        );
    }

    public function download(User $user, UploadedDocument $uploadedDocument): bool
    {
        return $this->userHasPermissionInTenant(
            $user,
            $uploadedDocument->tenant,
            Permission::DownloadDocuments,
        );
    }
}
