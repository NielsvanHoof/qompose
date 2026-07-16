<?php

declare(strict_types=1);

namespace App\Queries\Tenancy;

use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

final class GetActiveTenantMembershipsForUser
{
    /**
     * @return Collection<int, TenantMembership>
     */
    public function handle(User $user): Collection
    {
        return TenantMembership::query()
            ->with('tenant:id,name,slug')
            ->whereBelongsTo($user)
            ->where('status', TenantMembershipStatus::Active)
            ->get()
            ->sortBy(
                fn (TenantMembership $membership): string => $this->resolveTenant($membership)->name,
            )
            ->values();
    }

    private function resolveTenant(TenantMembership $membership): Tenant
    {
        $tenant = $membership->tenant;

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('Tenant membership is missing its tenant.');
        }

        return $tenant;
    }
}
