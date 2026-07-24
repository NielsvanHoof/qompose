<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Dossiers\Builder\ApplyQuestionnaireTemplateToDossierAction;
use App\Actions\Dossiers\Builder\CreateDocumentRequestAction;
use App\Actions\Dossiers\Builder\DeleteDocumentRequestAction;
use App\Actions\Dossiers\Builder\ReorderDocumentRequestsAction;
use App\Actions\Dossiers\Builder\UpdateDocumentRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\Builder\ApplyQuestionnaireTemplateRequest;
use App\Http\Requests\Dossiers\Builder\ReorderDocumentRequestsRequest;
use App\Http\Requests\Dossiers\Builder\StoreDocumentRequestRequest;
use App\Http\Requests\Dossiers\Builder\UpdateDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Form-builder mutations for dossier questionnaire items.
 */
final class DocumentRequestController extends Controller
{
    public function store(
        Tenant $tenant,
        StoreDocumentRequestRequest $request,
        Dossier $dossier,
        CreateDocumentRequestAction $createDocumentRequest,
    ): RedirectResponse {
        $createDocumentRequest->handle($dossier, $request->validated());

        return $this->redirectToBuilder($dossier);
    }

    public function update(
        Tenant $tenant,
        UpdateDocumentRequestRequest $request,
        Dossier $dossier,
        DocumentRequest $documentRequest,
        UpdateDocumentRequestAction $updateDocumentRequest,
    ): RedirectResponse {
        $updateDocumentRequest->handle($documentRequest, $request->validated());

        return $this->redirectToBuilder($dossier);
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

        return $this->redirectToBuilder($dossier);
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

        return $this->redirectToBuilder($dossier);
    }

    public function applyTemplate(
        Tenant $tenant,
        ApplyQuestionnaireTemplateRequest $request,
        Dossier $dossier,
        ApplyQuestionnaireTemplateToDossierAction $applyQuestionnaireTemplateToDossier,
    ): RedirectResponse {
        // Action resolves the QuestionnaireTemplate so this controller stays in Dossiers.
        $applyQuestionnaireTemplateToDossier->handle(
            $dossier,
            (int) $request->validated('questionnaire_template_id'),
        );

        return $this->redirectToBuilder($dossier);
    }

    /** Form builder mutations stay on the full-bleed builder page. */
    private function redirectToBuilder(Dossier $dossier): RedirectResponse
    {
        return to_route(
            'workspaces.dossiers.builder',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }
}
