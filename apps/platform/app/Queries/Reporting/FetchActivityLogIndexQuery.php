<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

use App\Enums\AuditEvent;
use App\Models\Activity;
use App\Models\ClientAccessGrant;
use App\Models\Tenant;
use App\Queries\Filters\ScoutSearchFilter;
use App\Queries\PaginatedIndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends PaginatedIndexQuery<Activity>
 */
final class FetchActivityLogIndexQuery extends PaginatedIndexQuery
{
    private Tenant $tenant;

    public function __construct(
        private readonly ActivityLogRowMapper $rowMapper,
    ) {}

    /**
     * @return LengthAwarePaginator<int, array{
     *     id: int,
     *     event: string|null,
     *     label: string,
     *     description: string,
     *     causer_name: string|null,
     *     subject: array{type: string, id: int, name: string|null}|null,
     *     created_at: string|null,
     *     properties: array{ip: string|null, route: string|null},
     *     attribute_changes: array{attributes: array<string, mixed>, old: array<string, mixed>}|null
     * }>
     */
    public function handle(Tenant $tenant): LengthAwarePaginator
    {
        $this->tenant = $tenant;

        /** @var LengthAwarePaginator<int, array{id: int, event: string|null, label: string, description: string, causer_name: string|null, subject: array{type: string, id: int, name: string|null}|null, created_at: string|null, properties: array{ip: string|null, route: string|null}, attribute_changes: array{attributes: array<string, mixed>, old: array<string, mixed>}|null}> */
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
                ['key' => 'description', 'type' => 'search', 'label' => __('Search')],
                [
                    'key' => 'event',
                    'type' => 'select',
                    'label' => __('Event'),
                    'options' => collect(AuditEvent::cases())
                        ->map(fn (AuditEvent $event): array => [
                            'value' => $event->value,
                            'label' => $event->label(),
                        ])
                        ->values()
                        ->all(),
                ],
            ],
            'sorts' => [
                ['key' => '-created_at', 'label' => __('Newest first')],
                ['key' => 'created_at', 'label' => __('Oldest first')],
                ['key' => 'event', 'label' => __('Event (A–Z)')],
                ['key' => '-event', 'label' => __('Event (Z–A)')],
            ],
            'defaults' => [
                'sort' => '-created_at',
                'per_page' => 15,
            ],
        ];
    }

    /**
     * @return Builder<Activity>
     */
    protected function subject(): Builder
    {
        return Activity::query()
            ->where('tenant_id', $this->tenant->id)
            ->with([
                'causer',
                'subject' => function ($morphTo): void {
                    if (! $morphTo instanceof MorphTo) {
                        return;
                    }

                    $morphTo->morphWith([
                        ClientAccessGrant::class => ['dossier:id,title'],
                    ]);
                },
            ]);
    }

    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('event'),
            ScoutSearchFilter::make(Activity::class, 'description'),
        ];
    }

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('created_at'),
            AllowedSort::field('event'),
        ];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    /**
     * @return array{
     *     id: int,
     *     event: string|null,
     *     label: string,
     *     description: string,
     *     causer_name: string|null,
     *     subject: array{type: string, id: int, name: string|null}|null,
     *     created_at: string|null,
     *     properties: array{ip: string|null, route: string|null},
     *     attribute_changes: array{attributes: array<string, mixed>, old: array<string, mixed>}|null
     * }
     */
    protected function mapModel(Model $model): array
    {
        /** @var Activity $model */
        return $this->rowMapper->map($model)->toArray();
    }
}
