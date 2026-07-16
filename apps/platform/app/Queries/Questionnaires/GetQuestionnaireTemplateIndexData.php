<?php

declare(strict_types=1);

namespace App\Queries\Questionnaires;

use App\Models\QuestionnaireTemplate;

final class GetQuestionnaireTemplateIndexData
{
    /**
     * @return array{
     *     system_templates: array<int, array{
     *         id: int,
     *         name: string,
     *         description: string|null,
     *         category: string,
     *         category_label: string,
     *         items_count: int,
     *         is_system: bool
     *     }>,
     *     firm_templates: array<int, array{
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
        $templates = QuestionnaireTemplate::queryVisibleToCurrentTenant()
            ->withCount('items')
            ->oldest('name')
            ->get();

        return [
            'system_templates' => $templates
                ->filter(fn (QuestionnaireTemplate $template): bool => $template->isSystem())
                ->values()
                ->map(fn (QuestionnaireTemplate $template): array => $this->summary($template))
                ->all(),
            'firm_templates' => $templates
                ->filter(fn (QuestionnaireTemplate $template): bool => ! $template->isSystem())
                ->values()
                ->map(fn (QuestionnaireTemplate $template): array => $this->summary($template))
                ->all(),
        ];
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
    private function summary(QuestionnaireTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category->value,
            'category_label' => $template->category->label(),
            'items_count' => $template->items_count,
            'is_system' => $template->isSystem(),
        ];
    }
}
