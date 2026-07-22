<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Actions\Audit\LogAuditActivityAction;
use App\Actions\Portal\CreateClientAccessGrantAction;
use App\Enums\AuditEvent;
use App\Enums\DossierReminderSource;
use App\Enums\DossierStatus;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Portal\ClientPortalReminderNotification;
use App\Queries\Dossiers\DossierHasOutstandingClientItemsQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class SendDossierReminderAction
{
    public function __construct(
        private readonly CreateClientAccessGrantAction $createClientAccessGrant,
        private readonly DossierHasOutstandingClientItemsQuery $dossierHasOutstandingClientItems,
        private readonly LogAuditActivityAction $logAuditActivity,
    ) {}

    public function handle(
        Dossier $dossier,
        User $grantCreator,
        DossierReminderSource $source,
        ?User $auditCauser = null,
    ): bool {
        return DB::transaction(function () use ($dossier, $grantCreator, $source, $auditCauser): bool {
            $dossierQuery = Dossier::query()->whereKey($dossier->getKey());
            $dossierQuery->getQuery()->lockForUpdate();
            $lockedDossier = $dossierQuery->firstOrFail();

            if (
                $source === DossierReminderSource::Scheduled
                && (
                    $lockedDossier->next_reminder_at === null
                    || $lockedDossier->next_reminder_at->isFuture()
                )
            ) {
                return false;
            }

            if ($lockedDossier->status === DossierStatus::Completed) {
                throw ValidationException::withMessages([
                    'dossier' => __('A completed dossier cannot receive reminders.'),
                ]);
            }

            if (! $this->dossierHasOutstandingClientItems->handle($lockedDossier)) {
                throw ValidationException::withMessages([
                    'dossier' => __('This dossier has no items awaiting the client.'),
                ]);
            }

            $lockedDossier->loadMissing(['client', 'tenant']);
            $client = $lockedDossier->client;

            if (! $client instanceof Client) {
                throw new RuntimeException('Dossier client is missing.');
            }

            $result = $this->createClientAccessGrant->handle(
                $lockedDossier,
                $grantCreator,
                7,
            );

            $firmName = $lockedDossier->tenant instanceof Tenant
                ? $lockedDossier->tenant->name
                : (string) config('app.name');
            $portalUrl = URL::route('portal.exchange', [
                'token' => $result['plain_text_token'],
            ]);

            Notification::route('mail', $client->email)
                ->notify(new ClientPortalReminderNotification(
                    grantId: $result['grant']->id,
                    dossier: $lockedDossier,
                    portalUrl: $portalUrl,
                    expiresAt: $result['grant']->expires_at,
                    firmName: $firmName,
                    source: $source,
                ));

            $nextReminderAt = $lockedDossier->reminder_interval_days === null
                ? null
                : now()->addDays($lockedDossier->reminder_interval_days);

            $lockedDossier->disableLogging();
            $lockedDossier->forceFill(['next_reminder_at' => $nextReminderAt])->save();

            $this->logAuditActivity->handle(
                AuditEvent::DossierReminderQueued,
                $lockedDossier,
                [
                    'grant_id' => $result['grant']->id,
                    'source' => $source->value,
                    'channel' => 'mail',
                    'next_reminder_at' => $nextReminderAt?->toIso8601String(),
                ],
                $auditCauser,
            );

            return true;
        });
    }
}
