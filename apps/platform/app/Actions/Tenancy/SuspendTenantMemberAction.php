<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Enums\TenantMembershipStatus;
use App\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\WorkspaceMemberRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SuspendTenantMemberAction
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly WorkspaceMemberRoles $memberRoles,
    ) {}

    public function handle(TenantMembership $membership, User $suspendedBy): TenantMembership
    {
        return DB::transaction(function () use ($membership, $suspendedBy): TenantMembership {
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

            if ($locked->status === TenantMembershipStatus::Suspended) {
                return $locked;
            }

            if ($this->memberRoles->isLastActiveOwner($member, $tenant)) {
                throw ValidationException::withMessages([
                    'membership' => __('The workspace must keep at least one owner.'),
                ]);
            }

            $locked->forceFill(['status' => TenantMembershipStatus::Suspended])->saveOrFail();

            $this->logAuditActivity->handle(
                AuditEvent::MemberSuspended,
                $locked,
                [
                    'user_id' => $member->id,
                    'email' => $member->email,
                    'role' => $this->memberRoles->roleFor($member, $tenant)?->value,
                ],
                $suspendedBy,
            );

            return $locked->fresh(['user', 'tenant']) ?? $locked;
        });
    }
}
