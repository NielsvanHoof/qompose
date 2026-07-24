<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Dossiers\DocumentRequests\ReviewDocumentRequestAction;
use App\Actions\Dossiers\DocumentRequests\SubmitStaffQuestionnaireAnswerAction;
use App\Enums\DocumentRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\DocumentRequests\ReviewDocumentRequestRequest;
use App\Http\Requests\Dossiers\DocumentRequests\StoreQuestionnaireAnswerRequest;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function array_key_exists;
use function is_string;

/**
 * Review-stage mutations: approve/request changes, and staff answers on behalf of the client.
 */
final class DocumentRequestReviewController extends Controller
{
    public function store(
        Tenant $tenant,
        ReviewDocumentRequestRequest $request,
        Dossier $dossier,
        DocumentRequest $documentRequest,
        ReviewDocumentRequestAction $reviewDocumentRequest,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $decision = DocumentRequestStatus::from((string) $request->validated('decision'));
        $rejectionReason = $request->validated('rejection_reason');

        $reviewDocumentRequest->handle(
            $documentRequest,
            $user,
            $decision,
            is_string($rejectionReason) ? $rejectionReason : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $decision === DocumentRequestStatus::Accepted
                ? __('Item approved.')
                : __('Changes requested. The client notification has been queued.'),
        ]);

        return $this->redirectToReview($dossier);
    }

    public function answer(
        Tenant $tenant,
        StoreQuestionnaireAnswerRequest $request,
        Dossier $dossier,
        DocumentRequest $documentRequest,
        SubmitStaffQuestionnaireAnswerAction $submitStaffQuestionnaireAnswer,
    ): RedirectResponse {
        $validated = $request->validated();

        $submitStaffQuestionnaireAnswer->handle(
            $documentRequest,
            $validated['answer_text'] ?? null,
            array_key_exists('answer_boolean', $validated)
                ? (bool) $validated['answer_boolean']
                : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Answer saved on behalf of the client.'),
        ]);

        return $this->redirectToReview($dossier);
    }

    private function redirectToReview(Dossier $dossier): RedirectResponse
    {
        return to_route(
            'workspaces.dossiers.review',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }
}
