<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Models\ClientAccessGrant;
use App\Models\Tenant;
use App\Notifications\Portal\ClientPortalInviteNotification;
use Illuminate\Notifications\Events\NotificationFailed;
use RuntimeException;
use Throwable;

final class LogClientPortalInviteFailed
{
    public function __construct(
        private readonly LogAuditActivity $logAuditActivity,
    ) {}

    public function handle(NotificationFailed $event): void
    {
        if (
            ! $event->notification instanceof ClientPortalInviteNotification
            || $event->channel !== 'mail'
        ) {
            return;
        }

        try {
            $grant = ClientAccessGrant::query()
                ->withoutGlobalScopes()
                ->whereKey($event->notification->grantId)
                ->first();

            if (! $grant instanceof ClientAccessGrant) {
                return;
            }

            $tenant = Tenant::query()->whereKey($grant->tenant_id)->first();

            if (! $tenant instanceof Tenant) {
                return;
            }

            $tenant->execute(function () use ($grant, $event): void {
                $this->logAuditActivity->handle(
                    AuditEvent::ClientPortalInviteFailed,
                    $grant,
                    [
                        'dossier_id' => $grant->dossier_id,
                        'channel' => $event->channel,
                    ],
                    includeRequestContext: false,
                );
            });
        } catch (Throwable $exception) {
            report(new RuntimeException(
                'Failed to audit a failed client portal invitation.',
                previous: $exception,
            ));
        }
    }
}
