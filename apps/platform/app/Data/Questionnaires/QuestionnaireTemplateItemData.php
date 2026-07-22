<?php

declare(strict_types=1);

namespace App\Data\Questionnaires;

/**
 * Template item on the questionnaire template show page.
 */
final readonly class QuestionnaireTemplateItemData
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public ?string $instructions,
        public int $sortOrder,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     type: string,
     *     title: string,
     *     instructions: string|null,
     *     sort_order: int
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'sort_order' => $this->sortOrder,
        ];
    }
}
