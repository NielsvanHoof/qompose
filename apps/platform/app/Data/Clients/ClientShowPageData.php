<?php

declare(strict_types=1);

namespace App\Data\Clients;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Full payload for the client show Inertia page.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class ClientShowPageData implements Arrayable
{
    /**
     * @param  LengthAwarePaginator<int, array{
     *     id: int,
     *     title: string,
     *     reference: string|null,
     *     status: string,
     *     client_name: string,
     *     updated_at: string
     * }>  $dossiers
     */
    public function __construct(
        public ClientSummaryData $client,
        public LengthAwarePaginator $dossiers,
    ) {}

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
    public function toArray(): array
    {
        return [
            'client' => $this->client->toArray(),
            'dossiers' => $this->dossiers,
        ];
    }
}
