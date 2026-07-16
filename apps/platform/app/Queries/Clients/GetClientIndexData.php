<?php

declare(strict_types=1);

namespace App\Queries\Clients;

use App\Models\Client;

final class GetClientIndexData
{
    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     dossiers_count: int
     * }>
     */
    public function handle(): array
    {
        return Client::query()
            ->withCount('dossiers')
            ->get(['id', 'name', 'email'])
            ->sortBy('name')
            ->values()
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'dossiers_count' => $client->dossiers_count,
            ])
            ->all();
    }
}
