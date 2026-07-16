<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Audit\LogAuditActivity;
use App\Actions\Dossiers\SubmitQuestionnaireAnswer;
use App\Enums\AuditEvent;
use App\Enums\DossierStatus;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Http\Requests\Portal\StorePortalQuestionnaireAnswerRequest;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use function array_key_exists;

final class ClientPortalAnswerController extends Controller
{
    /**
     * Accept a text or yes/no answer from the client portal.
     */
    public function store(
        StorePortalQuestionnaireAnswerRequest $request,
        string $token,
        DocumentRequest $documentRequest,
        SubmitQuestionnaireAnswer $submitQuestionnaireAnswer,
        LogAuditActivity $logAuditActivity,
    ): RedirectResponse {
        $grant = $this->grantFromRequest($request);

        abort_unless($documentRequest->dossier_id === $grant->dossier_id, 404);

        DB::transaction(function () use (
            $request,
            $documentRequest,
            $grant,
            $submitQuestionnaireAnswer,
            $logAuditActivity,
        ): void {
            $validated = $request->validated();

            $submittedDocumentRequest = $submitQuestionnaireAnswer(
                $documentRequest,
                $validated['answer_text'] ?? null,
                array_key_exists('answer_boolean', $validated)
                    ? (bool) $validated['answer_boolean']
                    : null,
            );

            $dossier = Dossier::query()->findOrFail($grant->dossier_id);

            // Mark dossier as awaiting review once the client starts answering.
            if (in_array($dossier->status, [DossierStatus::Draft, DossierStatus::AwaitingClient], true)) {
                $dossier->update(['status' => DossierStatus::InReview]);
            }

            $grant->forceFill(['last_used_at' => now()])->save();

            $logAuditActivity(
                AuditEvent::QuestionnaireAnswerSubmitted,
                $submittedDocumentRequest,
                [
                    'source' => 'client_portal',
                    'answer_type' => $submittedDocumentRequest->type->value,
                    'access_grant_id' => $grant->id,
                ],
            );
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Answer saved. Thank you.',
        ]);

        return to_route('portal.show', ['token' => $token]);
    }

    private function grantFromRequest(StorePortalQuestionnaireAnswerRequest $request): ClientAccessGrant
    {
        $grant = $request->attributes->get(ResolveClientPortalGrant::REQUEST_ATTRIBUTE);

        if (! $grant instanceof ClientAccessGrant) {
            abort(404);
        }

        return $grant;
    }
}
