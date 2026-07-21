<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Dossiers\ApplyQuestionnaireTemplateToDossierAction;
use App\Actions\Dossiers\CreateDocumentRequestAction;
use App\Actions\Dossiers\DeleteDocumentRequestAction;
use App\Actions\Dossiers\ReorderDocumentRequestsAction;
use App\Actions\Dossiers\SubmitQuestionnaireAnswerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\ApplyQuestionnaireTemplateRequest;
use App\Http\Requests\Dossiers\ReorderDocumentRequestsRequest;
use App\Http\Requests\Dossiers\StoreDocumentRequestRequest;
use App\Http\Requests\Dossiers\StoreQuestionnaireAnswerRequest;
use App\Http\Requests\Dossiers\UpdateDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\QuestionnaireTemplate;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class DocumentRequestController extends Controller
{
    public function store(
        Tenant $tenant,
        StoreDocumentRequestRequest $request,
        Dossier $dossier,
        CreateDocumentRequestAction $createDocumentRequest,
    ): RedirectResponse {
        $createDocumentRequest->handle($dossier, $request->validated());

        return $this->redirectToDossier($dossier);
    }

    public function update(
        Tenant $tenant,
        UpdateDocumentRequestRequest $request,
        Dossier $dossier,
        DocumentRequest $documentRequest,
    ): RedirectResponse {
        $documentRequest->update($request->validated());

        return $this->redirectToDossier($dossier);
    }

    public function destroy(
        Tenant $tenant,
        Dossier $dossier,
        DocumentRequest $documentRequest,
        DeleteDocumentRequestAction $deleteDocumentRequest,
    ): RedirectResponse {
        $this->authorize('view', $dossier);
        $this->authorize('delete', $documentRequest);

        $deleteDocumentRequest->handle($documentRequest);

        return $this->redirectToDossier($dossier);
    }

    public function reorder(
        Tenant $tenant,
        ReorderDocumentRequestsRequest $request,
        Dossier $dossier,
        ReorderDocumentRequestsAction $reorderDocumentRequests,
    ): RedirectResponse|Response {
        $reorderDocumentRequests->handle(
            $dossier,
            $request->array('document_request_ids'),
        );

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return $this->redirectToDossier($dossier);
    }

    public function applyTemplate(
        Tenant $tenant,
        ApplyQuestionnaireTemplateRequest $request,
        Dossier $dossier,
        ApplyQuestionnaireTemplateToDossierAction $applyQuestionnaireTemplateToDossier,
    ): RedirectResponse {
        $templateId = (int) $request->validated('questionnaire_template_id');

        $template = QuestionnaireTemplate::queryVisibleToCurrentTenant()
            ->whereKey($templateId)
            ->firstOrFail();

        $applyQuestionnaireTemplateToDossier->handle($dossier, $template);

        return $this->redirectToDossier($dossier);
    }

    public function answer(
        Tenant $tenant,
        StoreQuestionnaireAnswerRequest $request,
        Dossier $dossier,
        DocumentRequest $documentRequest,
        SubmitQuestionnaireAnswerAction $submitQuestionnaireAnswer,
    ): RedirectResponse {
        $validated = $request->validated();

        $submitQuestionnaireAnswer->handle(
            $documentRequest,
            $validated['answer_text'] ?? null,
            array_key_exists('answer_boolean', $validated)
                ? (bool) $validated['answer_boolean']
                : null,
        );

        return $this->redirectToDossier($dossier);
    }

    private function redirectToDossier(Dossier $dossier): RedirectResponse
    {
        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }
}
