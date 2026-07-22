<?php

declare(strict_types=1);

namespace App\Data\Reporting;

/**
 * Workspace dashboard metric counters.
 */
final readonly class DashboardMetricsData
{
    public function __construct(
        public int $clients,
        public int $openDossiers,
        public int $needsReview,
        public int $awaitingClient,
        public int $overdue,
        public int $inReview,
        public int $submittedDocumentRequests,
        public int $outstandingDocumentRequests,
    ) {}

    /**
     * @return array{
     *     clients: int,
     *     open_dossiers: int,
     *     needs_review: int,
     *     awaiting_client: int,
     *     overdue: int,
     *     in_review: int,
     *     submitted_document_requests: int,
     *     outstanding_document_requests: int
     * }
     */
    public function toArray(): array
    {
        return [
            'clients' => $this->clients,
            'open_dossiers' => $this->openDossiers,
            'needs_review' => $this->needsReview,
            'awaiting_client' => $this->awaitingClient,
            'overdue' => $this->overdue,
            'in_review' => $this->inReview,
            'submitted_document_requests' => $this->submittedDocumentRequests,
            'outstanding_document_requests' => $this->outstandingDocumentRequests,
        ];
    }
}
