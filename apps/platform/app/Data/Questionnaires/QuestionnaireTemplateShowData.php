<?php

declare(strict_types=1);

namespace App\Data\Questionnaires;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Full questionnaire template show payload.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class QuestionnaireTemplateShowData implements Arrayable
{
    /**
     * @param  list<QuestionnaireTemplateItemData>  $items
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $category,
        public string $categoryLabel,
        public bool $isSystem,
        public array $items,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     category: string,
     *     category_label: string,
     *     is_system: bool,
     *     items: list<array{
     *         id: int,
     *         type: string,
     *         title: string,
     *         instructions: string|null,
     *         sort_order: int
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'category_label' => $this->categoryLabel,
            'is_system' => $this->isSystem,
            'items' => array_map(
                static fn (QuestionnaireTemplateItemData $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}
