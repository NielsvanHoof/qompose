<?php

declare(strict_types=1);

namespace App\Queries\Portal;

use App\Data\Portal\ClientPortalPageData;
use App\Data\Portal\PortalDocumentRequestData;
use App\Data\Portal\PortalDossierData;
use App\Data\Portal\PortalFirmData;
use App\Data\Portal\PortalNextIncompleteData;
use App\Data\Portal\PortalProgressData;
use App\Data\Portal\PortalUploadedDocumentData;
use App\Enums\DocumentRequestStatus;
use App\Enums\SubmissionContext;
use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Transitions\DocumentRequestTransitions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function in_array;

final class FetchClientPortalQuery
{
    public function __construct(
        private readonly DocumentRequestTransitions $documentRequestTransitions,
    ) {}

    public function handle(ClientAccessGrant $grant): ClientPortalPageData
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
        $totalItemCount = $dossier->documentRequests->count();
        $remainingItems = $dossier->documentRequests->filter(
            fn (DocumentRequest $documentRequest): bool => in_array(
                $documentRequest->status->value,
                ['pending', 'rejected'],
                true,
            ),
        );
        $approvedItemCount = $dossier->documentRequests
            ->where('status', DocumentRequestStatus::Accepted)
            ->count();
        $nextIncompleteItem = $remainingItems->first();

        /** @var list<PortalDocumentRequestData> $documentRequests */
        $documentRequests = [];

        foreach ($dossier->documentRequests as $documentRequest) {
            $uploadedDocument = $documentRequest->uploadedDocument;

            $documentRequests[] = new PortalDocumentRequestData(
                id: $documentRequest->id,
                type: $documentRequest->type->value,
                title: $documentRequest->title,
                instructions: $documentRequest->instructions,
                status: $documentRequest->status->value,
                answerText: $documentRequest->answer_text,
                answerBoolean: $documentRequest->answer_boolean,
                rejectionReason: $documentRequest->rejection_reason,
                canRespond: $this->documentRequestTransitions->canSubmit(
                    $documentRequest,
                    SubmissionContext::Portal,
                    $dossier,
                ),
                uploadedDocument: $uploadedDocument === null
                    ? null
                    : new PortalUploadedDocumentData(
                        originalFilename: $uploadedDocument->original_filename,
                        sizeBytes: $uploadedDocument->size_bytes,
                        uploadedAt: $uploadedDocument->uploaded_at->toIso8601String(),
                    ),
            );
        }

        return new ClientPortalPageData(
            firm: new PortalFirmData(name: $tenant->name),
            dossier: new PortalDossierData(
                title: $dossier->title,
                reference: $dossier->reference,
                status: $dossier->status->value,
                dueDate: $dossier->due_date?->toDateString(),
                clientName: $client->name,
                expiresAt: $grant->expires_at->toIso8601String(),
                progress: new PortalProgressData(
                    total: $totalItemCount,
                    completed: $totalItemCount - $remainingItems->count(),
                    approved: $approvedItemCount,
                    remaining: $remainingItems->count(),
                    nextIncomplete: $nextIncompleteItem instanceof DocumentRequest
                        ? new PortalNextIncompleteData(
                            id: $nextIncompleteItem->id,
                            title: $nextIncompleteItem->title,
                        )
                        : null,
                ),
                documentRequests: $documentRequests,
            ),
        );
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
