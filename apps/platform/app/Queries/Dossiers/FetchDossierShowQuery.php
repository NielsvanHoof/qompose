<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Data\Dossiers\DossierAccessGrantData;
use App\Data\Dossiers\DossierClientSummaryData;
use App\Data\Dossiers\DossierReviewSummaryData;
use App\Data\Dossiers\DossierShowData;
use App\Data\Dossiers\StaffDocumentRequestData;
use App\Data\Dossiers\StaffUploadedDocumentData;
use App\Data\Shared\PersonOptionData;
use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use RuntimeException;

final class FetchDossierShowQuery
{
    public function handle(Dossier $dossier): DossierShowData
    {
        $dossier->load([
            'client:id,name,email',
            'responsibleUser:id,name,email',
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
        $outstandingClientItemCount = $documentRequests
            ->whereIn('status', [
                DocumentRequestStatus::Pending,
                DocumentRequestStatus::Rejected,
            ])
            ->count();

        /** @var list<StaffDocumentRequestData> $documentRequestRows */
        $documentRequestRows = [];

        foreach ($dossier->documentRequests as $documentRequest) {
            $uploaded = $documentRequest->uploadedDocument;
            $reviewer = $documentRequest->reviewedBy;

            $documentRequestRows[] = new StaffDocumentRequestData(
                id: $documentRequest->id,
                type: $documentRequest->type->value,
                title: $documentRequest->title,
                instructions: $documentRequest->instructions,
                status: $documentRequest->status->value,
                answerText: $documentRequest->answer_text,
                answerBoolean: $documentRequest->answer_boolean,
                answeredAt: $documentRequest->answered_at?->toIso8601String(),
                reviewedAt: $documentRequest->reviewed_at?->toIso8601String(),
                reviewedByName: $reviewer instanceof User ? $reviewer->name : null,
                rejectionReason: $documentRequest->rejection_reason,
                sortOrder: $documentRequest->sort_order,
                uploadedDocument: $uploaded === null ? null : new StaffUploadedDocumentData(
                    id: $uploaded->id,
                    originalFilename: $uploaded->original_filename,
                    sizeBytes: $uploaded->size_bytes,
                    uploadedAt: $uploaded->uploaded_at->toIso8601String(),
                    processingStatus: $uploaded->processing_status->value,
                    processingError: $uploaded->processing_error,
                ),
            );
        }

        /** @var list<DossierAccessGrantData> $accessGrants */
        $accessGrants = [];

        foreach ($dossier->clientAccessGrants as $grant) {
            $accessGrants[] = new DossierAccessGrantData(
                id: $grant->id,
                expiresAt: $grant->expires_at->toIso8601String(),
                revokedAt: $grant->revoked_at?->toIso8601String(),
                lastUsedAt: $grant->last_used_at?->toIso8601String(),
                isValid: $grant->isValid(),
            );
        }

        $responsibleUser = $dossier->responsibleUser;

        return new DossierShowData(
            id: $dossier->id,
            title: $dossier->title,
            reference: $dossier->reference,
            status: $dossier->status->value,
            dueDate: $dossier->due_date?->toDateString(),
            responsibleStaff: $responsibleUser instanceof User
                ? new PersonOptionData(
                    id: $responsibleUser->id,
                    name: $responsibleUser->name,
                    email: $responsibleUser->email,
                )
                : null,
            reminderIntervalDays: $dossier->reminder_interval_days,
            nextReminderAt: $dossier->next_reminder_at?->toIso8601String(),
            lastClientMessageSentAt: $dossier->last_client_message_sent_at?->toIso8601String(),
            lastClientOpenedAt: $dossier->last_client_opened_at?->toIso8601String(),
            hasOutstandingClientItems: $outstandingClientItemCount > 0,
            readyToComplete: $dossier->status !== DossierStatus::Completed
                && $totalRequestCount > 0
                && $acceptedRequestCount === $totalRequestCount,
            reviewSummary: new DossierReviewSummaryData(
                total: $totalRequestCount,
                pending: $documentRequests->where('status', DocumentRequestStatus::Pending)->count(),
                submitted: $documentRequests->where('status', DocumentRequestStatus::Submitted)->count(),
                accepted: $acceptedRequestCount,
                rejected: $documentRequests->where('status', DocumentRequestStatus::Rejected)->count(),
            ),
            client: new DossierClientSummaryData(
                name: $client->name,
                email: $client->email,
            ),
            documentRequests: $documentRequestRows,
            accessGrants: $accessGrants,
        );
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
