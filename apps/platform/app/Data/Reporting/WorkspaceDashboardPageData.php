<?php

declare(strict_types=1);

namespace App\Data\Reporting;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Full payload for the workspace dashboard Inertia page.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class WorkspaceDashboardPageData implements Arrayable
{
    /**
     * @param  list<DashboardRecentDossierData>  $recentDossiers
     */
    public function __construct(
        public DashboardMetricsData $metrics,
        public array $recentDossiers,
    ) {}

    /**
     * @return array{
     *     metrics: array{
     *         clients: int,
     *         open_dossiers: int,
     *         needs_review: int,
     *         awaiting_client: int,
     *         overdue: int,
     *         in_review: int,
     *         submitted_document_requests: int,
     *         outstanding_document_requests: int
     *     },
     *     recent_dossiers: list<array{
     *         id: int,
     *         title: string,
     *         reference: string|null,
     *         status: string,
     *         client_name: string,
     *         due_date: string|null,
     *         responsible_name: string|null,
     *         updated_at: string
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'metrics' => $this->metrics->toArray(),
            'recent_dossiers' => array_map(
                static fn (DashboardRecentDossierData $dossier): array => $dossier->toArray(),
                $this->recentDossiers,
            ),
        ];
    }
}
