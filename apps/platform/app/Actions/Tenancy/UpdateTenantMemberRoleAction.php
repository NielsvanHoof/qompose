<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\WorkspaceMemberRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateTenantMemberRoleAction
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly WorkspaceMemberRoles $memberRoles,
    ) {}

    public function handle(TenantMembership $membership, User $updatedBy, Role $role): TenantMembership
    {
        return DB::transaction(function () use ($membership, $updatedBy, $role): TenantMembership {
            $membershipQuery = TenantMembership::query()->whereKey($membership->getKey());
            $membershipQuery->getQuery()->lockForUpdate();
            $locked = $membershipQuery->firstOrFail();
            $locked->loadMissing(['user', 'tenant']);

            $tenant = $this->memberRoles->requireTenant($locked);
            $member = $locked->user;

            if (! $member instanceof User) {
                throw ValidationException::withMessages([
                    'membership' => __('This membership is invalid.'),
                ]);
            }

            if ($locked->status !== TenantMembershipStatus::Active) {
                throw ValidationException::withMessages([
                    'role' => __('Only active members can change roles.'),
                ]);
            }

            if (! $this->memberRoles->actorCanAssignRole($updatedBy, $tenant, $role)) {
                throw ValidationException::withMessages([
                    'role' => __('Only an owner can assign the owner role.'),
                ]);
            }

            $previousRole = $this->memberRoles->roleFor($member, $tenant);

            if ($previousRole === Role::Owner && $role !== Role::Owner
                && $this->memberRoles->isLastActiveOwner($member, $tenant)) {
                throw ValidationException::withMessages([
                    'role' => __('The workspace must keep at least one owner.'),
                ]);
            }

            if ($previousRole === $role) {
                return $locked;
            }

            $this->memberRoles->syncRole($member, $tenant, $role);

            $this->logAuditActivity->handle(
                AuditEvent::MemberRoleChanged,
                $locked,
                [
                    'user_id' => $member->id,
                    'email' => $member->email,
                    'old_role' => $previousRole?->value,
                    'new_role' => $role->value,
                ],
                $updatedBy,
            );

            return $locked->fresh(['user', 'tenant']) ?? $locked;
        });
    }
}
