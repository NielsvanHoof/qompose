<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use RuntimeException;

final class FetchWorkspaceDashboardQuery
{
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
     *     recent_dossiers: array<int, array{
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
    public function handle(Tenant $tenant): array
    {
        return [
            'metrics' => $this->getMetrics($tenant),
            'recent_dossiers' => $this->getRecentDossiers($tenant),
        ];
    }

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
    public function getMetrics(Tenant $tenant): array
    {
        $outstandingDocumentRequestsQuery = DocumentRequest::query()
            ->whereBelongsTo($tenant);

        $outstandingDocumentRequestsQuery->getQuery()->whereIn('status', [
            DocumentRequestStatus::Pending->value,
            DocumentRequestStatus::Rejected->value,
        ]);

        $submittedDocumentRequests = DocumentRequest::query()
            ->whereBelongsTo($tenant)
            ->where('status', DocumentRequestStatus::Submitted)
            ->toBase()
            ->count();

        $awaitingClientQuery = Dossier::query()
            ->whereBelongsTo($tenant)
            ->whereNot('status', DossierStatus::Completed)
            ->whereHas('documentRequests', function ($documentRequestQuery): void {
                $documentRequestQuery->getQuery()->whereIn('status', [
                    DocumentRequestStatus::Pending->value,
                    DocumentRequestStatus::Rejected->value,
                ]);
            });

        $needsReviewQuery = Dossier::query()
            ->whereBelongsTo($tenant)
            ->whereNot('status', DossierStatus::Completed)
            ->whereHas('documentRequests', fn ($documentRequestQuery) => $documentRequestQuery
                ->getQuery()
                ->where('status', DocumentRequestStatus::Submitted->value));
        $overdueQuery = clone $awaitingClientQuery;
        $overdueQuery->getQuery()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today());

        return [
            'clients' => Client::query()
                ->whereBelongsTo($tenant)
                ->toBase()
                ->count(),
            'open_dossiers' => Dossier::query()
                ->whereBelongsTo($tenant)
                ->whereNot('status', DossierStatus::Completed)
                ->toBase()
                ->count(),
            'needs_review' => $needsReviewQuery->toBase()->count(),
            'awaiting_client' => (clone $awaitingClientQuery)->toBase()->count(),
            'overdue' => $overdueQuery->toBase()->count(),
            'in_review' => Dossier::query()
                ->whereBelongsTo($tenant)
                ->where('status', DossierStatus::InReview)
                ->toBase()
                ->count(),
            'submitted_document_requests' => $submittedDocumentRequests,
            'outstanding_document_requests' => $outstandingDocumentRequestsQuery
                ->toBase()
                ->count(),
        ];
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     title: string,
     *     reference: string|null,
     *     status: string,
     *     client_name: string,
     *     due_date: string|null,
     *     responsible_name: string|null,
     *     updated_at: string
     * }>
     */
    public function getRecentDossiers(Tenant $tenant): array
    {
        $recentDossiersQuery = Dossier::query()
            ->whereBelongsTo($tenant)
            ->with(['client:id,name', 'responsibleUser:id,name'])
            ->latest();

        $recentDossiersQuery->getQuery()->limit(5);

        return $recentDossiersQuery
            ->get([
                'id',
                'client_id',
                'responsible_user_id',
                'title',
                'reference',
                'status',
                'due_date',
                'updated_at',
            ])
            ->map(function (Dossier $dossier): array {
                $client = $dossier->client;

                if (! $client instanceof Client) {
                    throw new RuntimeException('Dossier client is missing.');
                }

                return [
                    'id' => $dossier->id,
                    'title' => $dossier->title,
                    'reference' => $dossier->reference,
                    'status' => $dossier->status->value,
                    'client_name' => $client->name,
                    'due_date' => $dossier->due_date?->toDateString(),
                    'responsible_name' => $dossier->responsibleUser?->name,
                    'updated_at' => $dossier->updated_at->toDateTimeString(),
                ];
            })
            ->all();
    }
}
