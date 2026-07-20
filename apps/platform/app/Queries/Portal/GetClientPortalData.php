<?php

declare(strict_types=1);

namespace App\Queries\Portal;

use App\Enums\SubmissionContext;
use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Transitions\DocumentRequestTransitions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetClientPortalData
{
    public function __construct(
        private readonly DocumentRequestTransitions $documentRequestTransitions,
    ) {}

    /**
     * @return array{
     *     firm: array{name: string},
     *     dossier: array{
     *         title: string,
     *         reference: string|null,
     *         status: string,
     *         client: array{name: string},
     *         expires_at: string,
     *         document_requests: array<int, array{
     *             id: int,
     *             type: string,
     *             title: string,
     *             instructions: string|null,
     *             status: string,
     *             answer_text: string|null,
     *             answer_boolean: bool|null,
     *             rejection_reason: string|null,
     *             can_respond: bool,
     *             uploaded_document: array{
     *                 original_filename: string,
     *                 size_bytes: int,
     *                 uploaded_at: string
     *             }|null
     *         }>
     *     }
     * }
     */
    public function handle(ClientAccessGrant $grant): array
    {
        $dossier = Dossier::query()
            ->with([
                'client:id,name,email',
                'tenant:id,name',
                'documentRequests' => fn ($query) => $query
                    ->oldest('sort_order')
                    ->oldest('id')
                    ->with('uploadedDocument:id,document_request_id,original_filename,size_bytes,uploaded_at'),
            ])
            ->findOrFail($grant->dossier_id);

        $tenant = $this->resolveTenant($dossier);
        $client = $this->resolveClient($dossier);

        return [
            'firm' => [
                'name' => $tenant->name,
            ],
            'dossier' => [
                'title' => $dossier->title,
                'reference' => $dossier->reference,
                'status' => $dossier->status->value,
                'client' => [
                    'name' => $client->name,
                ],
                'expires_at' => $grant->expires_at->toIso8601String(),
                'document_requests' => $dossier->documentRequests
                    ->map(function (DocumentRequest $documentRequest) use ($dossier): array {
                        $uploadedDocument = $documentRequest->uploadedDocument;

                        return [
                            'id' => $documentRequest->id,
                            'type' => $documentRequest->type->value,
                            'title' => $documentRequest->title,
                            'instructions' => $documentRequest->instructions,
                            'status' => $documentRequest->status->value,
                            'answer_text' => $documentRequest->answer_text,
                            'answer_boolean' => $documentRequest->answer_boolean,
                            'rejection_reason' => $documentRequest->rejection_reason,
                            'can_respond' => $this->documentRequestTransitions->canSubmit(
                                $documentRequest,
                                SubmissionContext::Portal,
                                $dossier,
                            ),
                            'uploaded_document' => $uploadedDocument === null
                                ? null
                                : [
                                    'original_filename' => $uploadedDocument->original_filename,
                                    'size_bytes' => $uploadedDocument->size_bytes,
                                    'uploaded_at' => $uploadedDocument->uploaded_at->toIso8601String(),
                                ],
                        ];
                    })
                    ->all(),
            ],
        ];
    }

    private function resolveTenant(Dossier $dossier): Tenant
    {
        $tenant = $dossier->tenant;

        if (! $tenant instanceof Tenant) {
            throw (new ModelNotFoundException)->setModel(Tenant::class, [$dossier->tenant_id]);
        }

        return $tenant;
    }

    private function resolveClient(Dossier $dossier): Client
    {
        $client = $dossier->client;

        if (! $client instanceof Client) {
            throw (new ModelNotFoundException)->setModel(Client::class, [$dossier->client_id]);
        }

        return $client;
    }
}
