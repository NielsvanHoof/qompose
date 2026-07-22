<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

use App\Data\Reporting\DashboardMetricsData;
use App\Data\Reporting\DashboardRecentDossierData;
use App\Data\Reporting\WorkspaceDashboardPageData;
use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use RuntimeException;

final class FetchWorkspaceDashboardQuery
{
    public function handle(Tenant $tenant): WorkspaceDashboardPageData
    {
        return new WorkspaceDashboardPageData(
            metrics: $this->getMetrics($tenant),
            recentDossiers: $this->getRecentDossiers($tenant),
        );
    }

    public function getMetrics(Tenant $tenant): DashboardMetricsData
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

        return new DashboardMetricsData(
            clients: Client::query()
                ->whereBelongsTo($tenant)
                ->toBase()
                ->count(),
            openDossiers: Dossier::query()
                ->whereBelongsTo($tenant)
                ->whereNot('status', DossierStatus::Completed)
                ->toBase()
                ->count(),
            needsReview: $needsReviewQuery->toBase()->count(),
            awaitingClient: (clone $awaitingClientQuery)->toBase()->count(),
            overdue: $overdueQuery->toBase()->count(),
            inReview: Dossier::query()
                ->whereBelongsTo($tenant)
                ->where('status', DossierStatus::InReview)
                ->toBase()
                ->count(),
            submittedDocumentRequests: $submittedDocumentRequests,
            outstandingDocumentRequests: $outstandingDocumentRequestsQuery
                ->toBase()
                ->count(),
        );
    }

    /**
     * @return list<DashboardRecentDossierData>
     */
    public function getRecentDossiers(Tenant $tenant): array
    {
        $recentDossiersQuery = Dossier::query()
            ->whereBelongsTo($tenant)
            ->with(['client:id,name', 'responsibleUser:id,name'])
            ->latest();

        $recentDossiersQuery->getQuery()->limit(5);

        /** @var list<DashboardRecentDossierData> $rows */
        $rows = [];

        foreach ($recentDossiersQuery->get([
            'id',
            'client_id',
            'responsible_user_id',
            'title',
            'reference',
            'status',
            'due_date',
            'updated_at',
        ]) as $dossier) {
            $client = $dossier->client;

            if (! $client instanceof Client) {
                throw new RuntimeException('Dossier client is missing.');
            }

            $rows[] = new DashboardRecentDossierData(
                id: $dossier->id,
                title: $dossier->title,
                reference: $dossier->reference,
                status: $dossier->status->value,
                clientName: $client->name,
                dueDate: $dossier->due_date?->toDateString(),
                responsibleName: $dossier->responsibleUser?->name,
                updatedAt: $dossier->updated_at->toDateTimeString(),
            );
        }

        return $rows;
    }
}
