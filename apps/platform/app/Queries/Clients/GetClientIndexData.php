<?php

declare(strict_types=1);

namespace App\Queries\Clients;

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
final class GetClientIndexData extends PaginatedIndexQuery
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
        return [
            'id' => $model->id,
            'name' => $model->name,
            'email' => $model->email,
            'dossiers_count' => $model->dossiers_count,
        ];
    }
}
