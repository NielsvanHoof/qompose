<?php

declare(strict_types=1);

namespace App\Queries\Questionnaires;

use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;

final class GetQuestionnaireTemplateShowData
{
    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     category: string,
     *     category_label: string,
     *     is_system: bool,
     *     items: array<int, array{
     *         id: int,
     *         type: string,
     *         title: string,
     *         instructions: string|null,
     *         sort_order: int
     *     }>
     * }
     */
    public function handle(QuestionnaireTemplate $template): array
    {
        $template->load([
            'items' => fn ($query) => $query
                ->oldest('sort_order')
                ->oldest('id'),
        ]);

        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category->value,
            'category_label' => $template->category->label(),
            'is_system' => $template->isSystem(),
            'items' => $template->items
                ->map(fn (QuestionnaireTemplateItem $item): array => [
                    'id' => $item->id,
                    'type' => $item->type->value,
                    'title' => $item->title,
                    'instructions' => $item->instructions,
                    'sort_order' => $item->sort_order,
                ])
                ->all(),
        ];
    }
}
