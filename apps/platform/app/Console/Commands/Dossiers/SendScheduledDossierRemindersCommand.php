<?php

declare(strict_types=1);

namespace App\Console\Commands\Dossiers;

use App\Actions\Dossiers\SendDossierReminderAction;
use App\Enums\DocumentRequestStatus;
use App\Enums\DossierReminderSource;
use App\Enums\DossierStatus;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use App\Queries\Tenancy\FetchActiveUsersForTenantQuery;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class SendScheduledDossierRemindersCommand extends Command
{
    protected $signature = 'dossiers:send-reminders';

    protected $description = 'Queue due client reminders for every workspace';

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly FetchActiveUsersForTenantQuery $fetchActiveUsersForTenant,
        private readonly SendDossierReminderAction $sendDossierReminder,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $sentCount = 0;

        foreach (Tenant::query()->cursor() as $tenant) {
            $sentCount += $this->tenantContext->runForTenant(
                $tenant,
                fn (): int => $this->sendForCurrentTenant($tenant),
            );
        }

        $this->info(__('Queued :count scheduled client reminders.', [
            'count' => $sentCount,
        ]));

        return self::SUCCESS;
    }

    private function sendForCurrentTenant(Tenant $tenant): int
    {
        /** @var Collection<int, User> $activeUsers */
        $activeUsers = $this->fetchActiveUsersForTenant->handle($tenant);
        $fallbackUser = $activeUsers->first();

        if (! $fallbackUser instanceof User) {
            return 0;
        }

        $sentCount = 0;

        $dueDossiersQuery = Dossier::query()
            ->whereNot('status', DossierStatus::Completed)
            ->whereHas('documentRequests', function ($documentRequestQuery): void {
                $documentRequestQuery->getQuery()->whereIn('status', [
                    DocumentRequestStatus::Pending->value,
                    DocumentRequestStatus::Rejected->value,
                ]);
            });
        $dueDossiersQuery->getQuery()
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', now());

        $dueDossiersQuery->oldest('id')->chunkById(
            100,
            function (Collection $dossiers) use (
                $activeUsers,
                $fallbackUser,
                &$sentCount,
            ): void {
                foreach ($dossiers as $dossier) {
                    $grantCreator = $dossier->responsible_user_id === null
                        ? null
                        : $activeUsers->firstWhere('id', $dossier->responsible_user_id);

                    try {
                        $wasQueued = $this->sendDossierReminder->handle(
                            $dossier,
                            $grantCreator instanceof User ? $grantCreator : $fallbackUser,
                            DossierReminderSource::Scheduled,
                        );

                        if ($wasQueued) {
                            $sentCount++;
                        }
                    } catch (ValidationException) {
                        $dossier->disableLogging();
                        $dossier->forceFill(['next_reminder_at' => null])->save();
                    }
                }
            },
        );

        return $sentCount;
    }
}
