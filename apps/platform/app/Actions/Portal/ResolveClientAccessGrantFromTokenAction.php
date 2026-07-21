<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Models\ClientAccessGrant;

final class ResolveClientAccessGrantFromTokenAction
{
    /**
     * Look up a valid grant by the plain token from the magic link.
     * Bypasses the tenant global scope because no tenant is current yet.
     */
    public function handle(string $plainTextToken): ?ClientAccessGrant
    {
        if ($plainTextToken === '') {
            return null;
        }

        // withoutGlobalScopes: portal magic links resolve before a tenant is current.
        return ClientAccessGrant::query()
            ->withoutGlobalScopes()
            ->where('token', ClientAccessGrant::hashToken($plainTextToken))
            ->where('revoked_at', null)
            ->where('expires_at', '>', now())
            ->first();
    }
}
