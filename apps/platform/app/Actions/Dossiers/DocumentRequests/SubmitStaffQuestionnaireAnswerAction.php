<?php

declare(strict_types=1);

namespace App\Actions\Dossiers\DocumentRequests;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Enums\SubmissionContext;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Transitions\DossierTransitions;
use Illuminate\Support\Facades\DB;

/**
 * Record an answer received outside the portal and preserve staff attribution.
 */
final class SubmitStaffQuestionnaireAnswerAction
{
    public function __construct(
        private readonly SubmitQuestionnaireAnswerAction $submitQuestionnaireAnswer,
        private readonly DossierTransitions $dossierTransitions,
        private readonly LogAuditActivityAction $logAuditActivity,
    ) {}

    public function handle(
        DocumentRequest $documentRequest,
        ?string $answerText = null,
        ?bool $answerBoolean = null,
    ): DocumentRequest {
        return DB::transaction(function () use (
            $documentRequest,
            $answerText,
            $answerBoolean,
        ): DocumentRequest {
            $documentRequestQuery = DocumentRequest::query()
                ->whereKey($documentRequest->getKey());
            $documentRequestQuery->getQuery()->lockForUpdate();
            $lockedDocumentRequest = $documentRequestQuery->firstOrFail();

            $dossierQuery = Dossier::query()
                ->whereKey($lockedDocumentRequest->dossier_id);
            $dossierQuery->getQuery()->lockForUpdate();
            $dossier = $dossierQuery->firstOrFail();

            $submittedDocumentRequest = $this->submitQuestionnaireAnswer->handle(
                $lockedDocumentRequest,
                $answerText,
                $answerBoolean,
                SubmissionContext::Staff,
            );

            $this->dossierTransitions->markInReview($dossier);

            $this->logAuditActivity->handle(
                AuditEvent::QuestionnaireAnswerSubmitted,
                $submittedDocumentRequest,
                [
                    'source' => 'staff',
                    'answer_type' => $submittedDocumentRequest->type->value,
                ],
            );

            return $submittedDocumentRequest;
        });
    }
}
