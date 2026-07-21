<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\QuestionnaireTemplate;
use App\Models\User;
use RuntimeException;

final class FetchDossierShowQuery
{
    /**
     * @return array{
     *     templates: array<int, array{
     *         id: int,
     *         name: string,
     *         category_label: string,
     *         items_count: int,
     *         is_system: bool
     *     }>,
     *     dossier: array{
     *         id: int,
     *         title: string,
     *         reference: string|null,
     *         status: string,
     *         ready_to_complete: bool,
     *         review_summary: array{
     *             total: int,
     *             pending: int,
     *             submitted: int,
     *             accepted: int,
     *             rejected: int
     *         },
     *         client: array{name: string, email: string},
     *         document_requests: array<int, array{
     *             id: int,
     *             type: string,
     *             title: string,
     *             instructions: string|null,
     *             status: string,
     *             answer_text: string|null,
     *             answer_boolean: bool|null,
     *             answered_at: string|null,
     *             reviewed_at: string|null,
     *             reviewed_by_name: string|null,
     *             rejection_reason: string|null,
     *             sort_order: int,
     *             uploaded_document: array{
     *                 id: int,
     *                 original_filename: string,
     *                 size_bytes: int,
     *                 uploaded_at: string,
     *                 processing_status: string,
     *                 processing_error: string|null
     *             }|null
     *         }>,
     *         access_grants: array<int, array{
     *             id: int,
     *             expires_at: string,
     *             revoked_at: string|null,
     *             last_used_at: string|null,
     *             is_valid: bool
     *         }>
     *     }
     * }
     */
    public function handle(Dossier $dossier): array
    {
        $dossier->load([
            'client:id,name,email',
            'documentRequests' => fn ($query) => $query
                ->with(['uploadedDocument', 'reviewedBy:id,name'])
                ->oldest('sort_order'),
            'clientAccessGrants' => fn ($query) => $query->latest(),
        ]);

        $client = $this->resolveClient($dossier);
        $documentRequests = $dossier->documentRequests;
        $totalRequestCount = $documentRequests->count();
        $acceptedRequestCount = $documentRequests
            ->where('status', DocumentRequestStatus::Accepted)
            ->count();

        $templates = QuestionnaireTemplate::queryVisibleToCurrentTenant()
            ->withCount('items')
            ->oldest('name')
            ->get(['id', 'name', 'category', 'tenant_id']);

        return [
            'templates' => $templates
                ->map(fn (QuestionnaireTemplate $template): array => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'category_label' => $template->category->label(),
                    'items_count' => $template->items_count,
                    'is_system' => $template->isSystem(),
                ])
                ->all(),
            'dossier' => [
                'id' => $dossier->id,
                'title' => $dossier->title,
                'reference' => $dossier->reference,
                'status' => $dossier->status->value,
                'ready_to_complete' => $dossier->status !== DossierStatus::Completed
                    && $totalRequestCount > 0
                    && $acceptedRequestCount === $totalRequestCount,
                'review_summary' => [
                    'total' => $totalRequestCount,
                    'pending' => $documentRequests->where('status', DocumentRequestStatus::Pending)->count(),
                    'submitted' => $documentRequests->where('status', DocumentRequestStatus::Submitted)->count(),
                    'accepted' => $acceptedRequestCount,
                    'rejected' => $documentRequests->where('status', DocumentRequestStatus::Rejected)->count(),
                ],
                'client' => [
                    'name' => $client->name,
                    'email' => $client->email,
                ],
                'document_requests' => $dossier->documentRequests
                    ->map(function (DocumentRequest $documentRequest): array {
                        $uploaded = $documentRequest->uploadedDocument;
                        $reviewer = $documentRequest->reviewedBy;

                        return [
                            'id' => $documentRequest->id,
                            'type' => $documentRequest->type->value,
                            'title' => $documentRequest->title,
                            'instructions' => $documentRequest->instructions,
                            'status' => $documentRequest->status->value,
                            'answer_text' => $documentRequest->answer_text,
                            'answer_boolean' => $documentRequest->answer_boolean,
                            'answered_at' => $documentRequest->answered_at?->toIso8601String(),
                            'reviewed_at' => $documentRequest->reviewed_at?->toIso8601String(),
                            'reviewed_by_name' => $reviewer instanceof User ? $reviewer->name : null,
                            'rejection_reason' => $documentRequest->rejection_reason,
                            'sort_order' => $documentRequest->sort_order,
                            'uploaded_document' => $uploaded === null ? null : [
                                'id' => $uploaded->id,
                                'original_filename' => $uploaded->original_filename,
                                'size_bytes' => $uploaded->size_bytes,
                                'uploaded_at' => $uploaded->uploaded_at->toIso8601String(),
                                'processing_status' => $uploaded->processing_status->value,
                                'processing_error' => $uploaded->processing_error,
                            ],
                        ];
                    })
                    ->all(),
                'access_grants' => $dossier->clientAccessGrants
                    ->map(fn (ClientAccessGrant $grant): array => [
                        'id' => $grant->id,
                        'expires_at' => $grant->expires_at->toIso8601String(),
                        'revoked_at' => $grant->revoked_at?->toIso8601String(),
                        'last_used_at' => $grant->last_used_at?->toIso8601String(),
                        'is_valid' => $grant->isValid(),
                    ])
                    ->all(),
            ],
        ];
    }

    private function resolveClient(Dossier $dossier): Client
    {
        $client = $dossier->client;

        if (! $client instanceof Client) {
            throw new RuntimeException('Dossier client is missing.');
        }

        return $client;
    }
}
