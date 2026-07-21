<?php

declare(strict_types=1);

namespace App\Queries\Questionnaires;

use App\Models\QuestionnaireTemplate;
use App\Models\Tenant;
use App\Queries\Filters\ScoutSearchFilter;
use App\Queries\PaginatedIndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends PaginatedIndexQuery<QuestionnaireTemplate>
 */
final class GetQuestionnaireTemplateIndexData extends PaginatedIndexQuery
{
    /**
     * Dual paginated lists: system templates and firm-owned templates.
     * Shared filters/sort apply to both; each uses its own page param.
     *
     * @return array{
     *     system_templates: LengthAwarePaginator<int, array{
     *         id: int,
     *         name: string,
     *         description: string|null,
     *         category: string,
     *         category_label: string,
     *         items_count: int,
     *         is_system: bool
     *     }>,
     *     firm_templates: LengthAwarePaginator<int, array{
     *         id: int,
     *         name: string,
     *         description: string|null,
     *         category: string,
     *         category_label: string,
     *         items_count: int,
     *         is_system: bool
     *     }>
     * }
     */
    public function handle(): array
    {
        $tenant = Tenant::current();
        $tenantId = $tenant instanceof Tenant ? $tenant->getKey() : null;

        // Fresh QueryBuilder per bucket so filters/sorts apply independently with distinct page names.
        /** @var LengthAwarePaginator<int, array{id: int, name: string, description: string|null, category: string, category_label: string, items_count: int, is_system: bool}> $systemTemplates */
        $systemTemplates = $this->paginate(
            subject: QuestionnaireTemplate::queryVisibleToCurrentTenant()
                ->withCount('items')
                ->where('tenant_id', null),
            pageName: 'system_page',
        );

        $firmQuery = QuestionnaireTemplate::queryVisibleToCurrentTenant()
            ->withCount('items');

        if ($tenantId !== null) {
            $firmQuery->where('tenant_id', $tenantId);
        } else {
            // No current tenant: firm bucket stays empty.
            $firmQuery->whereKey([]);
        }

        /** @var LengthAwarePaginator<int, array{id: int, name: string, description: string|null, category: string, category_label: string, items_count: int, is_system: bool}> $firmTemplates */
        $firmTemplates = $this->paginate(
            subject: $firmQuery,
            pageName: 'firm_page',
        );

        return [
            'system_templates' => $systemTemplates,
            'firm_templates' => $firmTemplates,
        ];
    }

    /**
     * Unused by handle() — dual buckets pass their own subjects to paginate().
     *
     * @return Builder<QuestionnaireTemplate>
     */
    protected function subject(): Builder
    {
        return QuestionnaireTemplate::queryVisibleToCurrentTenant()->withCount('items');
    }

    protected function allowedFilters(): array
    {
        return [
            ScoutSearchFilter::make(QuestionnaireTemplate::class),
            AllowedFilter::exact('category'),
        ];
    }

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('name'),
            AllowedSort::field('category'),
            AllowedSort::field('items_count'),
        ];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     category: string,
     *     category_label: string,
     *     items_count: int,
     *     is_system: bool
     * }
     */
    protected function mapModel(Model $model): array
    {
        /** @var QuestionnaireTemplate $model */
        return [
            'id' => $model->id,
            'name' => $model->name,
            'description' => $model->description,
            'category' => $model->category->value,
            'category_label' => $model->category->label(),
            'items_count' => $model->items_count,
            'is_system' => $model->isSystem(),
        ];
    }
}
