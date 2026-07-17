<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Actions\Audit\LogAuditActivity;
use App\Actions\Portal\SendClientChangesRequestedNotification;
use App\Enums\AuditEvent;
use App\Enums\DocumentRequestStatus;
use App\Models\DocumentRequest;
use App\Models\UploadedDocument;
use App\Models\User;
use App\Transitions\DocumentRequestTransitions;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

use function in_array;

final class ReviewDocumentRequest
{
    public function __construct(
        private readonly LogAuditActivity $logAuditActivity,
        private readonly SendClientChangesRequestedNotification $sendClientChangesRequestedNotification,
        private readonly DocumentRequestTransitions $documentRequestTransitions,
    ) {}

    public function handle(
        DocumentRequest $documentRequest,
        User $reviewer,
        DocumentRequestStatus $decision,
        ?string $rejectionReason = null,
    ): DocumentRequest {
        if (! in_array($decision, [DocumentRequestStatus::Accepted, DocumentRequestStatus::Rejected], true)) {
            throw new InvalidArgumentException('A review decision must accept or reject the request.');
        }

        return DB::transaction(function () use (
            $documentRequest,
            $reviewer,
            $decision,
            $rejectionReason,
        ): DocumentRequest {
            $documentRequestQuery = DocumentRequest::query()->whereKey($documentRequest->getKey());
            $documentRequestQuery->getQuery()->lockForUpdate();
            $lockedDocumentRequest = $documentRequestQuery->firstOrFail();

            if ($decision === DocumentRequestStatus::Accepted) {
                $this->documentRequestTransitions->accept($lockedDocumentRequest, $reviewer);
            } else {
                $this->documentRequestTransitions->reject(
                    $lockedDocumentRequest,
                    $reviewer,
                    $rejectionReason,
                );
            }

            $uploadedDocument = $lockedDocumentRequest->uploadedDocument;

            if ($uploadedDocument instanceof UploadedDocument) {
                $uploadedDocument->update([
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => $lockedDocumentRequest->reviewed_at,
                    'rejection_reason' => $lockedDocumentRequest->rejection_reason,
                ]);
            }

            $this->logAuditActivity->handle(
                $decision === DocumentRequestStatus::Accepted
                    ? AuditEvent::DocumentRequestAccepted
                    : AuditEvent::DocumentRequestRejected,
                $lockedDocumentRequest,
                [
                    'dossier_id' => $lockedDocumentRequest->dossier_id,
                    'decision' => $decision->value,
                ],
                $reviewer,
            );

            if ($decision === DocumentRequestStatus::Rejected) {
                $this->sendClientChangesRequestedNotification->handle($lockedDocumentRequest);

                $this->logAuditActivity->handle(
                    AuditEvent::ClientChangesRequestedQueued,
                    $lockedDocumentRequest,
                    [
                        'dossier_id' => $lockedDocumentRequest->dossier_id,
                        'channel' => 'mail',
                    ],
                    $reviewer,
                );
            }

            return $lockedDocumentRequest->fresh() ?? $lockedDocumentRequest;
        });
    }
}
