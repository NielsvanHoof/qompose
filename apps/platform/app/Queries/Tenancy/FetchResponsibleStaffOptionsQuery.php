<?php

declare(strict_types=1);

namespace App\Queries\Tenancy;

use App\Data\Shared\PersonOptionData;
use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\User;

final class FetchResponsibleStaffOptionsQuery
{
    /**
     * @return list<PersonOptionData>
     */
    public function handle(Tenant $tenant): array
    {
        /** @var list<PersonOptionData> $options */
        $options = [];

        foreach (
            User::query()
                ->whereHas(
                    'tenantMemberships',
                    fn ($query) => $query
                        ->where('tenant_id', $tenant->id)
                        ->where('status', TenantMembershipStatus::Active),
                )
                ->oldest('name')
                ->get(['id', 'name', 'email']) as $user
        ) {
            $options[] = new PersonOptionData(
                id: $user->id,
                name: $user->name,
                email: $user->email,
            );
        }

        return $options;
    }
}
