<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Models\TenantInvitation;

final class ResolveTenantInvitationFromTokenAction
{
    public function handle(string $plainTextToken): ?TenantInvitation
    {
        // withoutGlobalScopes: accept links resolve before a tenant is current.
        return TenantInvitation::query()
            ->withoutGlobalScopes()
            ->where('token', TenantInvitation::hashToken($plainTextToken))
            ->first();
    }
}
