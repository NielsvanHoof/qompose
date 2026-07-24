<?php

declare(strict_types=1);

namespace App\Transitions;

use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Enums\QuestionnaireItemType;
use App\Enums\SubmissionContext;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

use function in_array;

/**
 * Guarded status transitions for questionnaire items on a dossier.
 *
 * Allowed flow: pending/rejected/submitted → submitted → accepted|rejected.
 * Accepted items cannot be submitted again.
 * Portal submissions are locked while an item awaits review; staff may replace submitted uploads.
 */
final class DocumentRequestTransitions
{
    /**
     * Whether the document request can receive a submission in the given context.
     */
    public function canSubmit(
        DocumentRequest $documentRequest,
        SubmissionContext $context,
        ?Dossier $dossier = null,
    ): bool {
        if ($context === SubmissionContext::Portal) {
            $dossier ??= $documentRequest->dossier;

            if (! $dossier instanceof Dossier || $dossier->status === DossierStatus::Completed) {
                return false;
            }

            return in_array($documentRequest->status, [
                DocumentRequestStatus::Pending,
                DocumentRequestStatus::Rejected,
            ], true);
        }

        return in_array($documentRequest->status, [
            DocumentRequestStatus::Pending,
            DocumentRequestStatus::Rejected,
            DocumentRequestStatus::Submitted,
        ], true);
    }

    public function submitAnswer(
        DocumentRequest $documentRequest,
        SubmissionContext $context,
        ?string $answerText = null,
        ?bool $answerBoolean = null,
    ): void {
        if ($documentRequest->type === QuestionnaireItemType::File) {
            throw new InvalidArgumentException('File items must be answered via upload.');
        }

        $this->ensureCanBeSubmitted($documentRequest, $context);

        if ($documentRequest->type->storesAnswerText()) {
            if ($answerText === null || mb_trim($answerText) === '') {
                throw new InvalidArgumentException('A text answer is required.');
            }

            $documentRequest->update([
                'answer_text' => mb_trim($answerText),
                'answer_boolean' => null,
                ...$this->submittedState(),
            ]);

            return;
        }

        if ($answerBoolean === null) {
            throw new InvalidArgumentException('A yes/no answer is required.');
        }

        $documentRequest->update([
            'answer_boolean' => $answerBoolean,
            'answer_text' => null,
            ...$this->submittedState(),
        ]);
    }

    public function submitUpload(
        DocumentRequest $documentRequest,
        SubmissionContext $context,
    ): void {
        if ($documentRequest->type !== QuestionnaireItemType::File) {
            throw new InvalidArgumentException('Only file items accept uploads.');
        }

        $this->ensureCanBeSubmitted($documentRequest, $context);
        $documentRequest->update($this->submittedState());
    }

    public function accept(DocumentRequest $documentRequest, User $reviewer): void
    {
        $this->ensureCanBeReviewed($documentRequest);

        $documentRequest->update([
            'status' => DocumentRequestStatus::Accepted,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function reject(
        DocumentRequest $documentRequest,
        User $reviewer,
        ?string $rejectionReason,
    ): void {
        $this->ensureCanBeReviewed($documentRequest);

        $normalizedRejectionReason = $rejectionReason === null
            ? null
            : mb_trim($rejectionReason);

        if ($normalizedRejectionReason === null || $normalizedRejectionReason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Explain what the client needs to correct.',
            ]);
        }

        $documentRequest->update([
            'status' => DocumentRequestStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $normalizedRejectionReason,
        ]);
    }

    private function ensureCanBeSubmitted(
        DocumentRequest $documentRequest,
        SubmissionContext $context,
    ): void {
        if ($this->canSubmit($documentRequest, $context)) {
            return;
        }

        throw ValidationException::withMessages([
            'document_request' => $context === SubmissionContext::Portal
                ? 'This item cannot be submitted from the client portal in its current state.'
                : 'An approved item cannot be submitted again.',
        ]);
    }

    private function ensureCanBeReviewed(DocumentRequest $documentRequest): void
    {
        if ($documentRequest->status === DocumentRequestStatus::Submitted) {
            return;
        }

        throw ValidationException::withMessages([
            'decision' => 'Only submitted items can be reviewed.',
        ]);
    }

    /**
     * Payload shared by answer and upload submissions.
     * Clears prior review metadata so a corrected item can be reviewed again.
     *
     * @return array{
     *     status: DocumentRequestStatus,
     *     answered_at: CarbonInterface,
     *     reviewed_by: null,
     *     reviewed_at: null,
     *     rejection_reason: null
     * }
     */
    private function submittedState(): array
    {
        return [
            'status' => DocumentRequestStatus::Submitted,
            'answered_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }
}
