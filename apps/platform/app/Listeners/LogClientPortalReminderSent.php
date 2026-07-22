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
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class LogClientPortalReminderSent
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(NotificationSent $event): void
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
                DB::transaction(function () use ($grant, $event): void {
                    $dossier = Dossier::query()->whereKey($grant->dossier_id)->firstOrFail();
                    $sentAt = now();

                    $dossier->disableLogging();
                    $dossier->forceFill([
                        'last_client_message_sent_at' => $sentAt,
                        'next_reminder_at' => $dossier->reminder_interval_days === null
                            ? null
                            : $sentAt->copy()->addDays($dossier->reminder_interval_days),
                    ])->save();

                    $this->logAuditActivity->handle(
                        AuditEvent::DossierReminderSent,
                        $dossier,
                        [
                            'grant_id' => $grant->id,
                            'source' => $event->notification->source->value,
                            'channel' => $event->channel,
                            'sent_at' => $sentAt->toIso8601String(),
                        ],
                        includeRequestContext: false,
                    );
                });
            });
        } catch (Throwable $exception) {
            report(new RuntimeException(
                'Failed to record a delivered client reminder.',
                previous: $exception,
            ));
        }
    }
}
