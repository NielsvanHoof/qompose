<?php

declare(strict_types=1);

namespace App\Queries\Tenancy;

use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\User;

final class FetchResponsibleStaffOptionsQuery
{
    /**
     * @return array<int, array{id: int, name: string, email: string}>
     */
    public function handle(Tenant $tenant): array
    {
        return User::query()
            ->whereHas(
                'tenantMemberships',
                fn ($query) => $query
                    ->where('tenant_id', $tenant->id)
                    ->where('status', TenantMembershipStatus::Active),
            )
            ->oldest('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->all();
    }
}
