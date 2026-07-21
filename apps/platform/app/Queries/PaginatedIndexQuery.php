<?php

declare(strict_types=1);

namespace App\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Shared Spatie Query Builder + pagination for workspace index lists.
 *
 * Concrete queries declare subject, filters, sorts, and row mapping.
 * Call paginate() from handle() so signatures can vary (e.g. Activity needs Tenant).
 * Toolbar metadata (filters/sorts) lives in the controllers, not here.
 *
 * @template TModel of Model
 */
abstract class PaginatedIndexQuery
{
    /**
     * Base Eloquent query before Spatie filters/sorts are applied.
     *
     * @return Builder<TModel>
     */
    abstract protected function subject(): Builder;

    /**
     * @return list<\Spatie\QueryBuilder\AllowedFilter|string>
     */
    abstract protected function allowedFilters(): array;

    /**
     * @return list<AllowedSort|string>
     */
    abstract protected function allowedSorts(): array;

    abstract protected function defaultSort(): AllowedSort|string;

    /**
     * Map a model to the Inertia row shape for this index.
     *
     * @param  TModel  $model
     * @return array<string, mixed>
     */
    abstract protected function mapModel(Model $model): array;

    protected function perPage(): int
    {
        return 15;
    }

    protected function pageName(): string
    {
        return 'page';
    }

    /**
     * Build a Spatie QueryBuilder with allowed filters/sorts applied from the request.
     *
     * @param  Builder<TModel>|null  $subject
     * @return QueryBuilder<TModel>
     */
    protected function queryBuilder(?Builder $subject = null): QueryBuilder
    {
        return QueryBuilder::for($subject ?? $this->subject())
            ->allowedFilters(...$this->allowedFilters())
            ->allowedSorts(...$this->allowedSorts())
            ->defaultSort($this->defaultSort());
    }

    /**
     * Paginate the configured query and map each row for Inertia.
     *
     * @param  Builder<TModel>|null  $subject
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    protected function paginate(?Builder $subject = null, ?string $pageName = null): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, TModel> $paginator */
        $paginator = $this->queryBuilder($subject)
            ->paginate(
                perPage: $this->perPage(),
                pageName: $pageName ?? $this->pageName(),
            )
            ->withQueryString();

        /** @var LengthAwarePaginator<int, array<string, mixed>> */
        return $paginator->through(
            function (Model $model): array {
                /** @var TModel $model */
                return $this->mapModel($model);
            },
        );
    }
}
