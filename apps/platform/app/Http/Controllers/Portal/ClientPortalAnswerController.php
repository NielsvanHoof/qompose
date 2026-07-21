<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\SubmitPortalAnswerAction;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Http\Requests\Portal\StorePortalQuestionnaireAnswerRequest;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

use function array_key_exists;

final class ClientPortalAnswerController extends Controller
{
    /**
     * Accept a text or yes/no answer from the client portal.
     */
    public function store(
        StorePortalQuestionnaireAnswerRequest $request,
        DocumentRequest $documentRequest,
        SubmitPortalAnswerAction $submitPortalAnswer,
    ): RedirectResponse {
        $grant = $this->grantFromRequest($request);

        abort_unless($documentRequest->dossier_id === $grant->dossier_id, 404);

        $validated = $request->validated();

        $submitPortalAnswer->handle(
            $documentRequest,
            $grant,
            $validated['answer_text'] ?? null,
            array_key_exists('answer_boolean', $validated)
                ? (bool) $validated['answer_boolean']
                : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Answer saved. Thank you.'),
        ]);

        return to_route('portal.show');
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
