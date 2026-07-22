<?php

declare(strict_types=1);

namespace App\Queries\Clients;

use App\Data\Clients\ClientIndexRowData;
use App\Models\Client;
use App\Queries\Filters\ScoutSearchFilter;
use App\Queries\PaginatedIndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends PaginatedIndexQuery<Client>
 */
final class FetchClientIndexQuery extends PaginatedIndexQuery
{
    /**
     * @return LengthAwarePaginator<int, array{id: int, name: string, email: string, dossiers_count: int}>
     */
    public function handle(): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, array{id: int, name: string, email: string, dossiers_count: int}> */
        return $this->paginate();
    }

    /**
     * @return array{
     *     filters: list<array<string, mixed>>,
     *     sorts: list<array{key: string, label: string}>,
     *     defaults: array{sort: string, per_page: int}
     * }
     */
    public function toolbarMetadata(): array
    {
        return [
            'filters' => [
                ['key' => 'q', 'type' => 'search', 'label' => __('Search')],
            ],
            'sorts' => [
                ['key' => 'name', 'label' => __('Name (A–Z)')],
                ['key' => '-name', 'label' => __('Name (Z–A)')],
                ['key' => 'email', 'label' => __('Email (A–Z)')],
                ['key' => '-email', 'label' => __('Email (Z–A)')],
                ['key' => '-dossiers_count', 'label' => __('Most dossiers')],
                ['key' => 'dossiers_count', 'label' => __('Fewest dossiers')],
                ['key' => '-created_at', 'label' => __('Newest first')],
                ['key' => 'created_at', 'label' => __('Oldest first')],
            ],
            'defaults' => [
                'sort' => 'name',
                'per_page' => 15,
            ],
        ];
    }

    /**
     * @return Builder<Client>
     */
    protected function subject(): Builder
    {
        // Prefer Model::select() so phpstan-strict-rules does not flag instance->select().
        return Client::select(['id', 'name', 'email', 'created_at'])
            ->withCount('dossiers');
    }

    protected function allowedFilters(): array
    {
        return [
            ScoutSearchFilter::make(Client::class),
        ];
    }

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('name'),
            AllowedSort::field('email'),
            AllowedSort::field('dossiers_count'),
            AllowedSort::field('created_at'),
        ];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    /**
     * @return array{id: int, name: string, email: string, dossiers_count: int}
     */
    protected function mapModel(Model $model): array
    {
        /** @var Client $model */
        return (new ClientIndexRowData(
            id: $model->id,
            name: $model->name,
            email: $model->email,
            dossiersCount: $model->dossiers_count,
        ))->toArray();
    }
}
