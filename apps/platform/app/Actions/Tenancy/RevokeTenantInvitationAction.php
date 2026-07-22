<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class RevokeTenantInvitationAction
{
    public function __construct(private readonly LogAuditActivityAction $logAuditActivity) {}

    public function handle(TenantInvitation $invitation, User $revokedBy): void
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation has already been accepted.'),
            ]);
        }

        if ($invitation->isRevoked()) {
            return;
        }

        $invitation->forceFill(['revoked_at' => now()])->saveOrFail();

        $this->logAuditActivity->handle(
            AuditEvent::MemberInvitationRevoked,
            $invitation,
            [
                'email' => $invitation->email,
                'role' => $invitation->role->value,
            ],
            $revokedBy,
        );
    }
}
