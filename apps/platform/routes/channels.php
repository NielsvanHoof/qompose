<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
 * Private workspace channels — active members of the tenant may subscribe.
 * Echo channel name: private-workspaces.{slug}
 */
Broadcast::channel('workspaces.{tenantSlug}', function (User $user, string $tenantSlug): bool {
    $tenant = Tenant::query()->where('slug', $tenantSlug)->first();

    if (! $tenant instanceof Tenant) {
        return false;
    }

    // Same membership rules as EnsureValidTenantMembership.
    $user->loadMissing('tenantMemberships');

    return $user->belongsToTenant($tenant);
});
