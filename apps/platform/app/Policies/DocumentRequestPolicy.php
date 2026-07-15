<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\DocumentRequest;
use App\Models\Tenant;
use App\Models\User;

final class DocumentRequestPolicy
{
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateDossiers->value);
    }

    public function view(User $user, DocumentRequest $documentRequest): bool
    {
        $tenant = $documentRequest->tenant;

        return $user->can(Permission::ViewDossiers->value)
            && $tenant instanceof Tenant
            && $user->belongsToTenant($tenant);
    }

    /**
     * Staff upload onto a request (e.g. files received by email).
     */
    public function upload(User $user, DocumentRequest $documentRequest): bool
    {
        $tenant = $documentRequest->tenant;

        return $user->can(Permission::CreateDossiers->value)
            && $tenant instanceof Tenant
            && $user->belongsToTenant($tenant);
    }
}
