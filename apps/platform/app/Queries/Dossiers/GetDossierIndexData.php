<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Models\Client;
use App\Models\Dossier;
use RuntimeException;

final class GetDossierIndexData
{
    /**
     * @return array<int, array{
     *     id: int,
     *     client_name: string,
     *     title: string,
     *     reference: string|null,
     *     status: string
     * }>
     */
    public function handle(): array
    {
        return Dossier::query()
            ->with('client:id,name')
            ->latest()
            ->get(['id', 'client_id', 'title', 'reference', 'status'])
            ->map(function (Dossier $dossier): array {
                $client = $dossier->client;

                if (! $client instanceof Client) {
                    throw new RuntimeException('Dossier client is missing.');
                }

                return [
                    'id' => $dossier->id,
                    'client_name' => $client->name,
                    'title' => $dossier->title,
                    'reference' => $dossier->reference,
                    'status' => $dossier->status->value,
                ];
            })
            ->all();
    }
}
