<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Models\Client;
use App\Models\Dossier;
use App\Queries\PaginatedIndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends PaginatedIndexQuery<Dossier>
 */
final class FetchArchivedDossiersQuery extends PaginatedIndexQuery
{
    /**
     * @return LengthAwarePaginator<int, array{
     *     id: int,
     *     title: string,
     *     reference: string|null,
     *     status: string,
     *     client_name: string,
     *     client_archived: bool,
     *     archived_at: string
     * }>
     */
    public function handle(): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, array{id: int, title: string, reference: string|null, status: string, client_name: string, client_archived: bool, archived_at: string}> */
        return $this->paginate();
    }

    public function toolbarMetadata(): array
    {
        return [
            'filters' => [
                ['key' => 'q', 'type' => 'search', 'label' => __('Search archive')],
            ],
            'sorts' => [
                ['key' => '-deleted_at', 'label' => __('Recently archived')],
                ['key' => 'deleted_at', 'label' => __('Oldest archived')],
                ['key' => 'title', 'label' => __('Title (A–Z)')],
                ['key' => '-title', 'label' => __('Title (Z–A)')],
            ],
            'defaults' => [
                'sort' => '-deleted_at',
                'per_page' => 15,
            ],
        ];
    }

    /** @return Builder<Dossier> */
    protected function subject(): Builder
    {
        $query = Dossier::onlyTrashed();
        $query->getQuery()->select(['id', 'client_id', 'title', 'reference', 'status', 'deleted_at']);

        return $query->with([
            'client' => fn ($query) => $query
                ->withTrashed()
                ->select(['id', 'name', 'deleted_at']),
        ]);
    }

    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::callback('q', function (Builder $query, mixed $value): void {
                if (! is_string($value) || mb_trim($value) === '') {
                    return;
                }

                $search = '%'.mb_trim($value).'%';
                $clientIds = Client::withTrashed();
                $clientIds->getQuery()
                    ->select('id')
                    ->whereLike('name', $search);

                $query->getQuery()->where(function (QueryBuilder $query) use ($search, $clientIds): void {
                    $query
                        ->whereLike('title', $search)
                        ->orWhereLike('reference', $search)
                        ->orWhereIn('client_id', $clientIds->getQuery());
                });
            }),
        ];
    }

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('deleted_at'),
            AllowedSort::field('title'),
        ];
    }

    protected function defaultSort(): string
    {
        return '-deleted_at';
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     reference: string|null,
     *     status: string,
     *     client_name: string,
     *     client_archived: bool,
     *     archived_at: string
     * }
     */
    protected function mapModel(Model $model): array
    {
        /** @var Dossier $model */
        $client = $model->client;

        if (! $client instanceof Client) {
            throw new RuntimeException('Archived dossier client is missing.');
        }

        return [
            'id' => $model->id,
            'title' => $model->title,
            'reference' => $model->reference,
            'status' => $model->status->value,
            'client_name' => $client->name,
            'client_archived' => $client->trashed(),
            'archived_at' => $model->deleted_at?->toIso8601String() ?? '',
        ];
    }
}
