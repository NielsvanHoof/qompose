<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Models\Client;

final class GetDossierCreateData
{
    /**
     * @return array<int, array{id: int, name: string, email: string}>
     */
    public function __invoke(): array
    {
        return Client::query()
            ->get(['id', 'name', 'email'])
            ->sortBy('name')
            ->values()
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ])
            ->all();
    }
}
