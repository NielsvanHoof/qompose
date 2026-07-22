<?php

declare(strict_types=1);

namespace App\Data\Questionnaires;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Dual-list questionnaire templates index payload.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class QuestionnaireTemplatesPageData implements Arrayable
{
    /**
     * @param  LengthAwarePaginator<int, array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     category: string,
     *     category_label: string,
     *     items_count: int,
     *     is_system: bool
     * }>  $systemTemplates
     * @param  LengthAwarePaginator<int, array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     category: string,
     *     category_label: string,
     *     items_count: int,
     *     is_system: bool
     * }>  $firmTemplates
     */
    public function __construct(
        public LengthAwarePaginator $systemTemplates,
        public LengthAwarePaginator $firmTemplates,
    ) {}

    /**
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
    public function toArray(): array
    {
        return [
            'system_templates' => $this->systemTemplates,
            'firm_templates' => $this->firmTemplates,
        ];
    }
}
