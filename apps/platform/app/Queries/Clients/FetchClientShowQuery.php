<?php

declare(strict_types=1);

namespace App\Queries\Clients;

use App\Data\Clients\ClientDossierRowData;
use App\Data\Clients\ClientShowPageData;
use App\Data\Clients\ClientSummaryData;
use App\Models\Client;
use App\Models\Dossier;

final class FetchClientShowQuery
{
    public function handle(Client $client): ClientShowPageData
    {
        $activeDossiersCount = $client->dossiers()->toBase()->count();
        $archivedDossiersQuery = Dossier::onlyTrashed();
        $archivedDossiersQuery->getQuery()->where('client_id', $client->id);
        $archivedDossiersCount = $archivedDossiersQuery->toBase()->count();

        $dossiers = Dossier::query()
            ->whereBelongsTo($client)
            ->latest('updated_at')
            ->paginate(perPage: 15, pageName: 'dossiers_page')
            ->withQueryString()
            ->through(fn (Dossier $dossier): array => (new ClientDossierRowData(
                id: $dossier->id,
                title: $dossier->title,
                reference: $dossier->reference,
                status: $dossier->status->value,
                clientName: $client->name,
                updatedAt: $dossier->updated_at->toIso8601String(),
            ))->toArray());

        return new ClientShowPageData(
            client: new ClientSummaryData(
                id: $client->id,
                name: $client->name,
                email: $client->email,
                activeDossiersCount: $activeDossiersCount,
                archivedDossiersCount: $archivedDossiersCount,
            ),
            dossiers: $dossiers,
        );
    }
}
