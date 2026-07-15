<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use App\Models\User;

final class UploadedDocumentPolicy
{
    public function download(User $user, UploadedDocument $uploadedDocument): bool
    {
        $tenant = $uploadedDocument->tenant;

        return $user->can(Permission::DownloadDocuments->value)
            && $tenant instanceof Tenant
            && $user->belongsToTenant($tenant);
    }
}
