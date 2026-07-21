<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Models\Client;
use App\Models\Dossier;
use App\Queries\Filters\ScoutSearchFilter;
use App\Queries\PaginatedIndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends PaginatedIndexQuery<Dossier>
 */
final class FetchDossierIndexQuery extends PaginatedIndexQuery
{
    /**
     * @return LengthAwarePaginator<int, array{
     *     id: int,
     *     client_name: string,
     *     title: string,
     *     reference: string|null,
     *     status: string
     * }>
     */
    public function handle(): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, array{id: int, client_name: string, title: string, reference: string|null, status: string}> */
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
                [
                    'key' => 'status',
                    'type' => 'select',
                    'label' => __('Status'),
                    'options' => [
                        ['value' => 'draft', 'label' => __('Draft')],
                        ['value' => 'awaiting_client', 'label' => __('Awaiting client')],
                        ['value' => 'in_review', 'label' => __('In review')],
                        ['value' => 'completed', 'label' => __('Completed')],
                    ],
                ],
                ['key' => 'client', 'type' => 'search', 'label' => __('Client')],
            ],
            'sorts' => [
                ['key' => '-updated_at', 'label' => __('Recently updated')],
                ['key' => 'updated_at', 'label' => __('Oldest updated')],
                ['key' => 'title', 'label' => __('Title (A–Z)')],
                ['key' => '-title', 'label' => __('Title (Z–A)')],
                ['key' => 'status', 'label' => __('Status (A–Z)')],
                ['key' => '-created_at', 'label' => __('Newest first')],
                ['key' => 'created_at', 'label' => __('Oldest first')],
            ],
            'defaults' => [
                'sort' => '-updated_at',
                'per_page' => 15,
            ],
        ];
    }

    /**
     * @return Builder<Dossier>
     */
    protected function subject(): Builder
    {
        // Prefer Model::select() so phpstan-strict-rules does not flag instance->select().
        return Dossier::select(['id', 'client_id', 'title', 'reference', 'status', 'created_at', 'updated_at'])
            ->with('client:id,name');
    }

    protected function allowedFilters(): array
    {
        return [
            ScoutSearchFilter::make(Dossier::class),
            AllowedFilter::exact('status'),
            AllowedFilter::partial('client', 'client.name'),
        ];
    }

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('title'),
            AllowedSort::field('status'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];
    }

    protected function defaultSort(): string
    {
        return '-updated_at';
    }

    /**
     * @return array{id: int, client_name: string, title: string, reference: string|null, status: string}
     */
    protected function mapModel(Model $model): array
    {
        /** @var Dossier $model */
        $client = $model->client;

        if (! $client instanceof Client) {
            throw new RuntimeException('Dossier client is missing.');
        }

        return [
            'id' => $model->id,
            'client_name' => $client->name,
            'title' => $model->title,
            'reference' => $model->reference,
            'status' => $model->status->value,
        ];
    }
}
