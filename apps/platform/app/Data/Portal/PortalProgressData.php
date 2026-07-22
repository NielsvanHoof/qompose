<?php

declare(strict_types=1);

namespace App\Data\Portal;

/**
 * Progress summary for the client portal questionnaire.
 */
final readonly class PortalProgressData
{
    public function __construct(
        public int $total,
        public int $completed,
        public int $approved,
        public int $remaining,
        public ?PortalNextIncompleteData $nextIncomplete,
    ) {}

    /**
     * @return array{
     *     total: int,
     *     completed: int,
     *     approved: int,
     *     remaining: int,
     *     next_incomplete: array{id: int, title: string}|null
     * }
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'completed' => $this->completed,
            'approved' => $this->approved,
            'remaining' => $this->remaining,
            'next_incomplete' => $this->nextIncomplete?->toArray(),
        ];
    }
}
