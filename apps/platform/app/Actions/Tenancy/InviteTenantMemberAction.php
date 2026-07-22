<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Enums\Role;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\Tenancy\WorkspaceMemberInviteNotification;
use App\Support\Tenancy\WorkspaceMemberRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InviteTenantMemberAction
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly WorkspaceMemberRoles $memberRoles,
    ) {}

    /**
     * @return array{invitation: TenantInvitation, plain_text_token: string}
     */
    public function handle(Tenant $tenant, User $invitedBy, string $email, Role $role): array
    {
        $email = mb_strtolower(mb_trim($email));

        if (! $this->memberRoles->actorCanAssignRole($invitedBy, $tenant, $role)) {
            throw ValidationException::withMessages([
                'role' => __('Only an owner can assign the owner role.'),
            ]);
        }

        return DB::transaction(function () use ($tenant, $invitedBy, $email, $role): array {
            $existingMemberQuery = TenantMembership::query()
                ->where('tenant_id', $tenant->id)
                ->whereHas('user', fn ($query) => $query->where('email', $email));

            if ($existingMemberQuery->toBase()->exists()) {
                throw ValidationException::withMessages([
                    'email' => __('This person is already a member of this workspace.'),
                ]);
            }

            // Replace any pending invite for the same email so resends stay simple.
            $pendingInvites = TenantInvitation::query()
                ->where('tenant_id', $tenant->id)
                ->where('email', $email);

            TenantInvitation::constrainToPending($pendingInvites);

            $pendingInvites->toBase()->update(['revoked_at' => now()]);

            $plainTextToken = Str::random(64);
            $expiresAt = now()->addDays(7);

            $invitation = TenantInvitation::query()->create([
                'tenant_id' => $tenant->id,
                'email' => $email,
                'role' => $role,
                'token' => TenantInvitation::hashToken($plainTextToken),
                'invited_by' => $invitedBy->id,
                'expires_at' => $expiresAt,
            ]);

            $acceptUrl = URL::route('invitations.show', ['token' => $plainTextToken]);

            Notification::route('mail', $email)
                ->notify(new WorkspaceMemberInviteNotification(
                    invitationId: $invitation->id,
                    firmName: $tenant->name,
                    role: $role,
                    acceptUrl: $acceptUrl,
                    expiresAt: $expiresAt,
                ));

            $this->logAuditActivity->handle(
                AuditEvent::MemberInvited,
                $invitation,
                [
                    'email' => $email,
                    'role' => $role->value,
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
                $invitedBy,
            );

            return [
                'invitation' => $invitation,
                'plain_text_token' => $plainTextToken,
            ];
        });
    }
}
