<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\QuestionnaireTemplate;
use RuntimeException;

final class GetDossierShowData
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
     *             sort_order: int,
     *             uploaded_document: array{
     *                 id: int,
     *                 original_filename: string,
     *                 size_bytes: int,
     *                 uploaded_at: string
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
    public function __invoke(Dossier $dossier): array
    {
        $dossier->load([
            'client:id,name,email',
            'documentRequests' => fn ($query) => $query
                ->with('uploadedDocument')
                ->oldest('sort_order'),
            'clientAccessGrants' => fn ($query) => $query->latest(),
        ]);

        $client = $this->resolveClient($dossier);

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
                'client' => [
                    'name' => $client->name,
                    'email' => $client->email,
                ],
                'document_requests' => $dossier->documentRequests
                    ->map(function (DocumentRequest $documentRequest): array {
                        $uploaded = $documentRequest->uploadedDocument;

                        return [
                            'id' => $documentRequest->id,
                            'type' => $documentRequest->type->value,
                            'title' => $documentRequest->title,
                            'instructions' => $documentRequest->instructions,
                            'status' => $documentRequest->status->value,
                            'answer_text' => $documentRequest->answer_text,
                            'answer_boolean' => $documentRequest->answer_boolean,
                            'answered_at' => $documentRequest->answered_at?->toIso8601String(),
                            'sort_order' => $documentRequest->sort_order,
                            'uploaded_document' => $uploaded === null ? null : [
                                'id' => $uploaded->id,
                                'original_filename' => $uploaded->original_filename,
                                'size_bytes' => $uploaded->size_bytes,
                                'uploaded_at' => $uploaded->uploaded_at->toIso8601String(),
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
