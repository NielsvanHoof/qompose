<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Notifications\Tenancy\WorkspaceMemberInviteNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ResendTenantInvitationAction
{
    /**
     * Rotate the token and resend the invite email.
     *
     * @return array{invitation: TenantInvitation, plain_text_token: string}
     */
    public function handle(TenantInvitation $invitation): array
    {
        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation is no longer valid.'),
            ]);
        }

        $tenant = $invitation->tenant;

        if (! $tenant instanceof Tenant) {
            $invitation->loadMissing('tenant');
            $tenant = $invitation->tenant;
        }

        if (! $tenant instanceof Tenant) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation is no longer valid.'),
            ]);
        }

        $plainTextToken = Str::random(64);
        $expiresAt = now()->addDays(7);

        $invitation->forceFill([
            'token' => TenantInvitation::hashToken($plainTextToken),
            'expires_at' => $expiresAt,
        ])->saveOrFail();

        $acceptUrl = URL::route('invitations.show', ['token' => $plainTextToken]);

        Notification::route('mail', $invitation->email)
            ->notify(new WorkspaceMemberInviteNotification(
                invitationId: $invitation->id,
                firmName: $tenant->name,
                role: $invitation->role,
                acceptUrl: $acceptUrl,
                expiresAt: $expiresAt,
            ));

        return [
            'invitation' => $invitation->fresh() ?? $invitation,
            'plain_text_token' => $plainTextToken,
        ];
    }
}
