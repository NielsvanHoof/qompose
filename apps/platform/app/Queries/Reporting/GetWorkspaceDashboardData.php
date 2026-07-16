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

final class GetWorkspaceDashboardData
{
    /**
     * @return array{
     *     metrics: array{
     *         clients: int,
     *         open_dossiers: int,
     *         awaiting_client: int,
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
     *         updated_at: string
     *     }>
     * }
     */
    public function handle(Tenant $tenant): array
    {
        $recentDossiersQuery = Dossier::query()
            ->whereBelongsTo($tenant)
            ->with('client:id,name')
            ->latest();

        $recentDossiersQuery->getQuery()->limit(5);

        $recentDossiers = $recentDossiersQuery
            ->get(['id', 'client_id', 'title', 'reference', 'status', 'updated_at']);

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

        return [
            'metrics' => [
                'clients' => Client::query()
                    ->whereBelongsTo($tenant)
                    ->toBase()
                    ->count(),
                'open_dossiers' => Dossier::query()
                    ->whereBelongsTo($tenant)
                    ->whereNot('status', DossierStatus::Completed)
                    ->toBase()
                    ->count(),
                'awaiting_client' => Dossier::query()
                    ->whereBelongsTo($tenant)
                    ->where('status', DossierStatus::AwaitingClient)
                    ->toBase()
                    ->count(),
                'in_review' => Dossier::query()
                    ->whereBelongsTo($tenant)
                    ->where('status', DossierStatus::InReview)
                    ->toBase()
                    ->count(),
                'submitted_document_requests' => $submittedDocumentRequests,
                'outstanding_document_requests' => $outstandingDocumentRequestsQuery
                    ->toBase()
                    ->count(),
            ],
            'recent_dossiers' => $recentDossiers
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
                        'updated_at' => $dossier->updated_at->toDateTimeString(),
                    ];
                })
                ->all(),
        ];
    }
}
