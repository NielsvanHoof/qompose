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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use function in_array;

final class ReviewDocumentRequest
{
    public function __construct(
        private readonly LogAuditActivity $logAuditActivity,
        private readonly SendClientChangesRequestedNotification $sendClientChangesRequestedNotification,
    ) {}

    public function __invoke(
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

            if ($lockedDocumentRequest->status !== DocumentRequestStatus::Submitted) {
                throw ValidationException::withMessages([
                    'decision' => 'Only submitted items can be reviewed.',
                ]);
            }

            $normalizedRejectionReason = $rejectionReason === null
                ? null
                : mb_trim($rejectionReason);

            if ($decision === DocumentRequestStatus::Rejected && $normalizedRejectionReason === '') {
                throw ValidationException::withMessages([
                    'rejection_reason' => 'Explain what the client needs to correct.',
                ]);
            }

            $reviewedAt = now();
            $storedRejectionReason = $decision === DocumentRequestStatus::Rejected
                ? $normalizedRejectionReason
                : null;

            $lockedDocumentRequest->update([
                'status' => $decision,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => $reviewedAt,
                'rejection_reason' => $storedRejectionReason,
            ]);

            $uploadedDocument = $lockedDocumentRequest->uploadedDocument;

            if ($uploadedDocument instanceof UploadedDocument) {
                $uploadedDocument->update([
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => $reviewedAt,
                    'rejection_reason' => $storedRejectionReason,
                ]);
            }

            ($this->logAuditActivity)(
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
                ($this->sendClientChangesRequestedNotification)($lockedDocumentRequest);

                ($this->logAuditActivity)(
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
