<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Notifications\Portal\ClientPortalReminderNotification;
use App\Tenancy\TenantContext;
use Illuminate\Notifications\Events\NotificationFailed;
use RuntimeException;
use Throwable;

final class LogClientPortalReminderFailed
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(NotificationFailed $event): void
    {
        if (
            ! $event->notification instanceof ClientPortalReminderNotification
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

            $this->tenantContext->runForTenant($tenant, function () use ($grant, $event): void {
                $dossier = Dossier::query()->whereKey($grant->dossier_id)->firstOrFail();

                $this->logAuditActivity->handle(
                    AuditEvent::DossierReminderFailed,
                    $dossier,
                    [
                        'grant_id' => $grant->id,
                        'source' => $event->notification->source->value,
                        'channel' => $event->channel,
                    ],
                    includeRequestContext: false,
                );
            });
        } catch (Throwable $exception) {
            report(new RuntimeException(
                'Failed to audit a failed client reminder.',
                previous: $exception,
            ));
        }
    }
}
