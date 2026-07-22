<?php

declare(strict_types=1);

namespace App\Data\Dossiers;

/**
 * Apply-template option on the dossier show page.
 */
final readonly class QuestionnaireTemplateOptionData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $categoryLabel,
        public int $itemsCount,
        public bool $isSystem,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
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
            'category_label' => $this->categoryLabel,
            'items_count' => $this->itemsCount,
            'is_system' => $this->isSystem,
        ];
    }
}
