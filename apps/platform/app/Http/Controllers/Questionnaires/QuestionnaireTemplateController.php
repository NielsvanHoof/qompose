<?php

declare(strict_types=1);

namespace App\Http\Controllers\Questionnaires;

use App\Actions\Audit\LogAuditActivityAction;
use App\Actions\Questionnaires\CopyQuestionnaireTemplateAction;
use App\Actions\Questionnaires\CreateQuestionnaireTemplateAction;
use App\Enums\AuditEvent;
use App\Enums\QuestionnaireTemplateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Questionnaires\StoreQuestionnaireTemplateRequest;
use App\Http\Requests\Questionnaires\UpdateQuestionnaireTemplateRequest;
use App\Models\QuestionnaireTemplate;
use App\Models\Tenant;
use App\Queries\Questionnaires\FetchQuestionnaireTemplateShowQuery;
use App\Queries\Questionnaires\FetchQuestionnaireTemplatesQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class QuestionnaireTemplateController extends Controller
{
    public function index(
        Tenant $tenant,
        FetchQuestionnaireTemplatesQuery $fetchQuestionnaireTemplates,
    ): Response {
        $this->authorize('viewAny', QuestionnaireTemplate::class);

        return Inertia::render('questionnaires/index', [
            ...$fetchQuestionnaireTemplates->handle()->toArray(),
            'categories' => collect(QuestionnaireTemplateCategory::cases())
                ->map(fn (QuestionnaireTemplateCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                ]),
            'can_manage' => request()->user()?->can('create', QuestionnaireTemplate::class) ?? false,
            ...$fetchQuestionnaireTemplates->indexQueryProps(),
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        $this->authorize('create', QuestionnaireTemplate::class);

        return Inertia::render('questionnaires/create', [
            'categories' => collect(QuestionnaireTemplateCategory::cases())
                ->map(fn (QuestionnaireTemplateCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                ]),
        ]);
    }

    public function store(
        Tenant $tenant,
        StoreQuestionnaireTemplateRequest $request,
        CreateQuestionnaireTemplateAction $createQuestionnaireTemplate,
    ): RedirectResponse {
        $template = $createQuestionnaireTemplate->handle($tenant, $request->validated());

        return to_route(
            'workspaces.templates.show',
            $this->workspaceRouteParameters(['template' => $template]),
        );
    }

    public function show(
        Tenant $tenant,
        QuestionnaireTemplate $template,
        FetchQuestionnaireTemplateShowQuery $getQuestionnaireTemplateShowData,
    ): Response {
        $this->authorize('view', $template);

        return Inertia::render('questionnaires/show', [
            'template' => $getQuestionnaireTemplateShowData->handle($template)->toArray(),
            'categories' => collect(QuestionnaireTemplateCategory::cases())
                ->map(fn (QuestionnaireTemplateCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                ]),
            'can_manage' => request()->user()?->can('update', $template) ?? false,
            'can_copy' => request()->user()?->can('copy', $template) ?? false,
        ]);
    }

    public function update(
        Tenant $tenant,
        UpdateQuestionnaireTemplateRequest $request,
        QuestionnaireTemplate $template,
    ): RedirectResponse {
        $template->update($request->validated());

        return to_route(
            'workspaces.templates.show',
            $this->workspaceRouteParameters(['template' => $template]),
        );
    }

    public function destroy(
        Tenant $tenant,
        QuestionnaireTemplate $template,
        LogAuditActivityAction $logAuditActivity,
    ): RedirectResponse {
        $this->authorize('delete', $template);

        $logAuditActivity->handle(
            AuditEvent::QuestionnaireTemplateDeleted,
            $template,
            [
                'name' => $template->name,
                'category' => $template->category->value,
            ],
        );

        $template->delete();

        return to_route(
            'workspaces.templates.index',
            $this->workspaceRouteParameters(),
        );
    }

    public function copy(
        Tenant $tenant,
        QuestionnaireTemplate $template,
        CopyQuestionnaireTemplateAction $copyQuestionnaireTemplate,
    ): RedirectResponse {
        $this->authorize('copy', $template);

        $copy = $copyQuestionnaireTemplate->handle($template);

        return to_route(
            'workspaces.templates.show',
            $this->workspaceRouteParameters(['template' => $copy]),
        );
    }
}
