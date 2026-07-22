<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\WorkspaceMemberRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RemoveTenantMemberAction
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly WorkspaceMemberRoles $memberRoles,
    ) {}

    public function handle(TenantMembership $membership, User $removedBy): void
    {
        DB::transaction(function () use ($membership, $removedBy): void {
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

            if ($this->memberRoles->isLastActiveOwner($member, $tenant)) {
                throw ValidationException::withMessages([
                    'membership' => __('The workspace must keep at least one owner.'),
                ]);
            }

            $role = $this->memberRoles->roleFor($member, $tenant);

            $this->logAuditActivity->handle(
                AuditEvent::MemberRemoved,
                $locked,
                [
                    'user_id' => $member->id,
                    'email' => $member->email,
                    'role' => $role?->value,
                ],
                $removedBy,
            );

            $this->memberRoles->clearRoles($member, $tenant);
            $locked->delete();
        });
    }
}
