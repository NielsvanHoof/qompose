<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Audit\LogAuditActivity;
use App\Actions\Dossiers\ApplyQuestionnaireTemplateToDossier;
use App\Actions\Dossiers\DeleteDocumentRequest;
use App\Actions\Dossiers\SubmitQuestionnaireAnswer;
use App\Enums\AuditEvent;
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
use Illuminate\Support\Facades\DB;

final class DocumentRequestController extends Controller
{
    public function store(
        Tenant $tenant,
        StoreDocumentRequestRequest $request,
        Dossier $dossier,
    ): RedirectResponse {
        // Aggregates live on the base query builder for strict PHPStan.
        $nextSortOrder = (int) $dossier->documentRequests()->toBase()->max('sort_order') + 1;

        $documentRequest = $dossier->documentRequests()->create([
            ...$request->validated(),
            'sort_order' => $nextSortOrder,
        ]);

        app(LogAuditActivity::class)(
            AuditEvent::DocumentRequestCreated,
            $documentRequest,
        );

        return $this->redirectToDossier($dossier);
    }

    public function update(
        Tenant $tenant,
        UpdateDocumentRequestRequest $request,
        Dossier $dossier,
        DocumentRequest $documentRequest,
    ): RedirectResponse {
        abort_unless($documentRequest->dossier_id === $dossier->id, 404);

        $documentRequest->update($request->validated());

        return $this->redirectToDossier($dossier);
    }

    public function destroy(
        Tenant $tenant,
        Dossier $dossier,
        DocumentRequest $documentRequest,
        DeleteDocumentRequest $deleteDocumentRequest,
    ): RedirectResponse {
        $this->authorize('view', $dossier);
        $this->authorize('delete', $documentRequest);
        abort_unless($documentRequest->dossier_id === $dossier->id, 404);

        $deleteDocumentRequest($documentRequest);

        return $this->redirectToDossier($dossier);
    }

    public function reorder(
        Tenant $tenant,
        ReorderDocumentRequestsRequest $request,
        Dossier $dossier,
    ): RedirectResponse {
        $ids = array_map('intval', $request->validated('document_request_ids'));
        $ownedIds = $dossier->documentRequests()
            ->toBase()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        abort_unless(
            count($ids) === count($ownedIds)
            && array_diff($ids, $ownedIds) === [],
            422,
        );

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $index => $id) {
                DocumentRequest::query()
                    ->whereKey($id)
                    ->update(['sort_order' => $index]);
            }
        });

        return $this->redirectToDossier($dossier);
    }

    public function applyTemplate(
        Tenant $tenant,
        ApplyQuestionnaireTemplateRequest $request,
        Dossier $dossier,
        ApplyQuestionnaireTemplateToDossier $applyQuestionnaireTemplateToDossier,
    ): RedirectResponse {
        $templateId = (int) $request->validated('questionnaire_template_id');

        $template = QuestionnaireTemplate::queryVisibleToCurrentTenant()
            ->whereKey($templateId)
            ->firstOrFail();

        $created = $applyQuestionnaireTemplateToDossier($dossier, $template);

        foreach ($created as $documentRequest) {
            app(LogAuditActivity::class)(
                AuditEvent::DocumentRequestCreated,
                $documentRequest,
            );
        }

        return $this->redirectToDossier($dossier);
    }

    public function answer(
        Tenant $tenant,
        StoreQuestionnaireAnswerRequest $request,
        Dossier $dossier,
        DocumentRequest $documentRequest,
        SubmitQuestionnaireAnswer $submitQuestionnaireAnswer,
    ): RedirectResponse {
        abort_unless($documentRequest->dossier_id === $dossier->id, 404);

        $validated = $request->validated();

        $submitQuestionnaireAnswer(
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
