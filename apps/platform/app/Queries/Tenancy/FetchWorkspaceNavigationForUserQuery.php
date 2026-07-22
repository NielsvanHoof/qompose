<?php

declare(strict_types=1);

namespace App\Queries\Tenancy;

use App\Data\Tenancy\WorkspaceNavItemData;
use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\User;

final class FetchWorkspaceNavigationForUserQuery
{
    /**
     * @return list<WorkspaceNavItemData>
     */
    public function handle(User $user): array
    {
        /** @var list<WorkspaceNavItemData> $workspaces */
        $workspaces = [];

        foreach (
            Tenant::query()
                ->whereHas('memberships', fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', TenantMembershipStatus::Active))
                ->get(['name', 'slug'])
                ->sortBy('name')
                ->values() as $tenant
        ) {
            $workspaces[] = new WorkspaceNavItemData(
                name: $tenant->name,
                slug: $tenant->slug,
            );
        }

        return $workspaces;
    }
}
