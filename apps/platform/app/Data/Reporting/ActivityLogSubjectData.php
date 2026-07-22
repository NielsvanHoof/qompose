<?php

declare(strict_types=1);

namespace App\Data\Reporting;

/**
 * Activity log subject chip.
 */
final readonly class ActivityLogSubjectData
{
    public function __construct(
        public string $type,
        public int $id,
        public ?string $name,
    ) {}

    /**
     * @return array{type: string, id: int, name: string|null}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
