<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Actions\Audit\LogAuditActivityAction;
use App\Actions\Dossiers\SubmitQuestionnaireAnswerAction;
use App\Enums\AuditEvent;
use App\Enums\SubmissionContext;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Transitions\DossierTransitions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class SubmitPortalAnswerAction
{
    public function __construct(
        private readonly SubmitQuestionnaireAnswerAction $submitQuestionnaireAnswer,
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly DossierTransitions $dossierTransitions,
        private readonly NotifyWorkspaceIfQuestionnaireCompleteAction $notifyWorkspaceIfQuestionnaireComplete,
    ) {}

    public function handle(
        DocumentRequest $documentRequest,
        ClientAccessGrant $grant,
        ?string $answerText = null,
        ?bool $answerBoolean = null,
    ): DocumentRequest {
        return DB::transaction(function () use (
            $documentRequest,
            $grant,
            $answerText,
            $answerBoolean,
        ): DocumentRequest {
            $documentRequestQuery = DocumentRequest::query()->whereKey($documentRequest->getKey());
            $documentRequestQuery->getQuery()->lockForUpdate();
            $lockedDocumentRequest = $documentRequestQuery->firstOrFail();

            $dossierQuery = Dossier::query()->whereKey($lockedDocumentRequest->dossier_id);
            $dossierQuery->getQuery()->lockForUpdate();
            $dossier = $dossierQuery->firstOrFail();

            $grantQuery = ClientAccessGrant::query()->whereKey($grant->getKey());
            $grantQuery->getQuery()->lockForUpdate();
            $lockedGrant = $grantQuery->firstOrFail();

            if ($lockedGrant->dossier_id !== $dossier->id) {
                throw (new ModelNotFoundException)->setModel(
                    DocumentRequest::class,
                    [$lockedDocumentRequest->getKey()],
                );
            }

            $submittedDocumentRequest = $this->submitQuestionnaireAnswer->handle(
                $lockedDocumentRequest,
                $answerText,
                $answerBoolean,
                SubmissionContext::Portal,
            );

            $this->dossierTransitions->markInReview($dossier);

            $lockedGrant->forceFill(['last_used_at' => now()])->save();

            $this->logAuditActivity->handle(
                AuditEvent::QuestionnaireAnswerSubmitted,
                $submittedDocumentRequest,
                [
                    'source' => 'client_portal',
                    'answer_type' => $submittedDocumentRequest->type->value,
                    'access_grant_id' => $lockedGrant->id,
                ],
            );

            // Toast workspace staff when this was the last outstanding item.
            $this->notifyWorkspaceIfQuestionnaireComplete->handle($dossier);

            return $submittedDocumentRequest;
        });
    }
}
