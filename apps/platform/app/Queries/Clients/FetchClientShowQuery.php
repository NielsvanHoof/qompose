<?php

declare(strict_types=1);

namespace App\Queries\Clients;

use App\Models\Client;
use App\Models\Dossier;
use Illuminate\Pagination\LengthAwarePaginator;

final class FetchClientShowQuery
{
    /**
     * @return array{
     *     client: array{
     *         id: int,
     *         name: string,
     *         email: string,
     *         active_dossiers_count: int,
     *         archived_dossiers_count: int
     *     },
     *     dossiers: LengthAwarePaginator<int, array{
     *         id: int,
     *         title: string,
     *         reference: string|null,
     *         status: string,
     *         client_name: string,
     *         updated_at: string
     *     }>
     * }
     */
    public function handle(Client $client): array
    {
        $activeDossiersCount = $client->dossiers()->toBase()->count();
        $archivedDossiersCount = $client->dossiers()->onlyTrashed()->toBase()->count();

        $dossiers = Dossier::query()
            ->whereBelongsTo($client)
            ->latest('updated_at')
            ->paginate(perPage: 15, pageName: 'dossiers_page')
            ->withQueryString()
            ->through(fn (Dossier $dossier): array => [
                'id' => $dossier->id,
                'title' => $dossier->title,
                'reference' => $dossier->reference,
                'status' => $dossier->status->value,
                'client_name' => $client->name,
                'updated_at' => $dossier->updated_at->toIso8601String(),
            ]);

        return [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'active_dossiers_count' => $activeDossiersCount,
                'archived_dossiers_count' => $archivedDossiersCount,
            ],
            'dossiers' => $dossiers,
        ];
    }
}
