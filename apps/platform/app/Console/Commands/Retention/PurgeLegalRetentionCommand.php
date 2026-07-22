<?php

declare(strict_types=1);

namespace App\Console\Commands\Retention;

use App\Actions\Retention\PurgeExpiredClientAction;
use App\Actions\Retention\PurgeExpiredDossierAction;
use App\Models\Client;
use App\Models\Dossier;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use function sprintf;

#[Signature('retention:purge-legal {--limit=50 : Maximum records to purge per model type}')]
#[Description('Permanently purge archived clients and dossiers past the legal retention period')]
final class PurgeLegalRetentionCommand extends Command
{
    public function handle(
        PurgeExpiredDossierAction $purgeExpiredDossier,
        PurgeExpiredClientAction $purgeExpiredClient,
    ): int {
        $limit = max(1, (int) $this->option('limit'));
        $cutoff = now()->subDays((int) config('retention.archived_days'));

        $dossierSummary = $this->purgeDossiers($purgeExpiredDossier, $cutoff, $limit);
        $clientSummary = $this->purgeClients($purgeExpiredClient, $cutoff, $limit);

        Log::info('Legal retention purge completed.', [
            'cutoff' => $cutoff->toIso8601String(),
            'dossiers_purged' => $dossierSummary['purged'],
            'clients_purged' => $clientSummary['purged'],
            'activity_rows_deleted' => $dossierSummary['activity_rows_deleted'] + $clientSummary['activity_rows_deleted'],
            'files_deleted' => $dossierSummary['files_deleted'],
        ]);

        $this->components->info(sprintf(
            'Purged %d dossier(s), %d client(s), deleted %d activity row(s) and %d file(s).',
            $dossierSummary['purged'],
            $clientSummary['purged'],
            $dossierSummary['activity_rows_deleted'] + $clientSummary['activity_rows_deleted'],
            $dossierSummary['files_deleted'],
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{purged: int, activity_rows_deleted: int, files_deleted: int}
     */
    private function purgeDossiers(PurgeExpiredDossierAction $purgeExpiredDossier, CarbonInterface $cutoff, int $limit): array
    {
        $summary = [
            'purged' => 0,
            'activity_rows_deleted' => 0,
            'files_deleted' => 0,
        ];

        $dossiers = Dossier::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($dossiers as $dossier) {
            $result = $purgeExpiredDossier->handle($dossier);

            if (! $result['purged']) {
                continue;
            }

            $summary['purged']++;
            $summary['activity_rows_deleted'] += $result['activity_rows_deleted'];
            $summary['files_deleted'] += $result['files_deleted'];
        }

        return $summary;
    }

    /**
     * @return array{purged: int, activity_rows_deleted: int}
     */
    private function purgeClients(PurgeExpiredClientAction $purgeExpiredClient, CarbonInterface $cutoff, int $limit): array
    {
        $summary = [
            'purged' => 0,
            'activity_rows_deleted' => 0,
        ];

        $clients = Client::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->whereDoesntHave('dossiers', fn ($query) => $query->withTrashed())
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($clients as $client) {
            $result = $purgeExpiredClient->handle($client);

            if (! $result['purged']) {
                continue;
            }

            $summary['purged']++;
            $summary['activity_rows_deleted'] += $result['activity_rows_deleted'];
        }

        return $summary;
    }
}
