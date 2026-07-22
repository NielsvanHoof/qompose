<?php

declare(strict_types=1);

namespace App\Data\Dossiers;

/**
 * Document-request status counts on dossier show.
 */
final readonly class DossierReviewSummaryData
{
    public function __construct(
        public int $total,
        public int $pending,
        public int $submitted,
        public int $accepted,
        public int $rejected,
    ) {}

    /**
     * @return array{
     *     total: int,
     *     pending: int,
     *     submitted: int,
     *     accepted: int,
     *     rejected: int
     * }
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'pending' => $this->pending,
            'submitted' => $this->submitted,
            'accepted' => $this->accepted,
            'rejected' => $this->rejected,
        ];
    }
}
