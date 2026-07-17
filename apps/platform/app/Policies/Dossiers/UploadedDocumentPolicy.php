<?php

declare(strict_types=1);

namespace App\Policies\Dossiers;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use App\Models\User;

final class UploadedDocumentPolicy
{
    /**
     * Staff can open the OCR extraction page when they can view dossiers.
     */
    public function view(User $user, UploadedDocument $uploadedDocument): bool
    {
        $tenant = $uploadedDocument->tenant;

        return $user->can(Permission::ViewDossiers->value)
            && $tenant instanceof Tenant
            && $user->belongsToTenant($tenant);
    }

    public function download(User $user, UploadedDocument $uploadedDocument): bool
    {
        $tenant = $uploadedDocument->tenant;

        return $user->can(Permission::DownloadDocuments->value)
            && $tenant instanceof Tenant
            && $user->belongsToTenant($tenant);
    }
}
