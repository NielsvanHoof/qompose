<?php

declare(strict_types=1);

namespace App\Queries\Tenancy;

use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Active staff members of a workspace (for broadcasting inbox notifications).
 */
final class GetActiveUsersForTenant
{
    /**
     * @return Collection<int, User>
     */
    public function handle(Tenant $tenant): Collection
    {
        return User::query()
            ->whereHas(
                'tenantMemberships',
                fn ($query) => $query
                    ->where('tenant_id', $tenant->getKey())
                    ->where('status', TenantMembershipStatus::Active),
            )
            ->get();
    }
}
