<?php

declare(strict_types=1);

namespace App\Queries\Tenancy;

use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\User;

final class GetWorkspaceNavigationForUser
{
    /**
     * @return list<array{name: string, slug: string}>
     */
    public function handle(User $user): array
    {
        $workspaces = Tenant::query()
            ->whereHas('memberships', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', TenantMembershipStatus::Active))
            ->get(['name', 'slug'])
            ->sortBy('name')
            ->values()
            ->map(fn (Tenant $tenant): array => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ])
            ->values()
            ->all();

        return array_values($workspaces);
    }
}
