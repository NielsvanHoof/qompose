<?php

declare(strict_types=1);

namespace App\Data\Questionnaires;

/**
 * Questionnaire template index / apply-template option row.
 */
final readonly class QuestionnaireTemplateRowData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $category,
        public string $categoryLabel,
        public int $itemsCount,
        public bool $isSystem,
    ) {}

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
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'category_label' => $this->categoryLabel,
            'items_count' => $this->itemsCount,
            'is_system' => $this->isSystem,
        ];
    }
}
