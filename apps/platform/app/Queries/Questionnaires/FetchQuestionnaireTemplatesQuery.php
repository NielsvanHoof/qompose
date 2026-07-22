<?php

declare(strict_types=1);

namespace App\Queries\Questionnaires;

use App\Data\Questionnaires\QuestionnaireTemplateRowData;
use App\Data\Questionnaires\QuestionnaireTemplatesPageData;
use App\Enums\QuestionnaireTemplateCategory;
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
final class FetchQuestionnaireTemplatesQuery extends PaginatedIndexQuery
{
    /**
     * Dual paginated lists: system templates and firm-owned templates.
     * Shared filters/sort apply to both; each uses its own page param.
     */
    public function handle(): QuestionnaireTemplatesPageData
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

        return new QuestionnaireTemplatesPageData(
            systemTemplates: $systemTemplates,
            firmTemplates: $firmTemplates,
        );
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
                    'key' => 'category',
                    'type' => 'select',
                    'label' => __('Category'),
                    'options' => collect(QuestionnaireTemplateCategory::cases())
                        ->map(fn (QuestionnaireTemplateCategory $category): array => [
                            'value' => $category->value,
                            'label' => $category->label(),
                        ])
                        ->values()
                        ->all(),
                ],
            ],
            'sorts' => [
                ['key' => 'name', 'label' => __('Name (A–Z)')],
                ['key' => '-name', 'label' => __('Name (Z–A)')],
                ['key' => 'category', 'label' => __('Category (A–Z)')],
                ['key' => '-items_count', 'label' => __('Most items')],
                ['key' => 'items_count', 'label' => __('Fewest items')],
            ],
            'defaults' => [
                'sort' => 'name',
                'per_page' => 15,
            ],
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
        return (new QuestionnaireTemplateRowData(
            id: $model->id,
            name: $model->name,
            description: $model->description,
            category: $model->category->value,
            categoryLabel: $model->category->label(),
            itemsCount: $model->items_count,
            isSystem: $model->isSystem(),
        ))->toArray();
    }
}
