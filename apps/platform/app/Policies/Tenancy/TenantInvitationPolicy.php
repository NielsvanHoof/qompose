<?php

declare(strict_types=1);

namespace App\Policies\Tenancy;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantPermission;

final class TenantInvitationPolicy
{
    use AuthorizesTenantPermission;

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageMembers->value);
    }

    public function resend(User $user, TenantInvitation $invitation): bool
    {
        return $this->manage($user, $invitation) && $invitation->isPending();
    }

    public function revoke(User $user, TenantInvitation $invitation): bool
    {
        return $this->manage($user, $invitation) && ! $invitation->isAccepted();
    }

    private function manage(User $user, TenantInvitation $invitation): bool
    {
        $invitation->loadMissing('tenant');
        $tenant = $invitation->tenant;

        if (! $tenant instanceof Tenant) {
            return false;
        }

        return $this->userHasPermissionInTenant(
            $user,
            $tenant,
            Permission::ManageMembers,
        );
    }
}
