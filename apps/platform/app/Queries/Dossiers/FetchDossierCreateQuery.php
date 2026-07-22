<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Data\Shared\PersonOptionData;
use App\Models\Client;

final class FetchDossierCreateQuery
{
    /**
     * @return list<PersonOptionData>
     */
    public function handle(): array
    {
        /** @var list<PersonOptionData> $clients */
        $clients = [];

        foreach (
            Client::query()
                ->get(['id', 'name', 'email'])
                ->sortBy('name')
                ->values() as $client
        ) {
            $clients[] = new PersonOptionData(
                id: $client->id,
                name: $client->name,
                email: $client->email,
            );
        }

        return $clients;
    }
}
