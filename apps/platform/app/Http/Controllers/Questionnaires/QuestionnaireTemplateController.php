<?php

declare(strict_types=1);

namespace App\Http\Controllers\Questionnaires;

use App\Actions\Questionnaires\CopyQuestionnaireTemplate;
use App\Enums\QuestionnaireTemplateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Questionnaires\StoreQuestionnaireTemplateRequest;
use App\Http\Requests\Questionnaires\UpdateQuestionnaireTemplateRequest;
use App\Models\QuestionnaireTemplate;
use App\Models\Tenant;
use App\Queries\Questionnaires\GetQuestionnaireTemplateIndexData;
use App\Queries\Questionnaires\GetQuestionnaireTemplateShowData;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class QuestionnaireTemplateController extends Controller
{
    public function index(
        GetQuestionnaireTemplateIndexData $getQuestionnaireTemplateIndexData,
    ): Response {
        $this->authorize('viewAny', QuestionnaireTemplate::class);

        return Inertia::render('questionnaires/index', [
            ...$getQuestionnaireTemplateIndexData(),
            'categories' => collect(QuestionnaireTemplateCategory::cases())
                ->map(fn (QuestionnaireTemplateCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                ]),
            'can_manage' => request()->user()?->can('create', QuestionnaireTemplate::class) ?? false,
        ]);
    }

    public function create(): Response
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

    public function store(StoreQuestionnaireTemplateRequest $request): RedirectResponse
    {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('Cannot create a template without an active tenant.');
        }

        $template = QuestionnaireTemplate::query()->create([
            ...$request->validated(),
            'tenant_id' => $tenant->getKey(),
        ]);

        return to_route('workspaces.templates.show', $template);
    }

    public function show(
        QuestionnaireTemplate $template,
        GetQuestionnaireTemplateShowData $getQuestionnaireTemplateShowData,
    ): Response {
        $this->authorize('view', $template);

        return Inertia::render('questionnaires/show', [
            'template' => $getQuestionnaireTemplateShowData($template),
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
        UpdateQuestionnaireTemplateRequest $request,
        QuestionnaireTemplate $template,
    ): RedirectResponse {
        $template->update($request->validated());

        return to_route('workspaces.templates.show', $template);
    }

    public function destroy(QuestionnaireTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return to_route('workspaces.templates.index');
    }

    public function copy(
        QuestionnaireTemplate $template,
        CopyQuestionnaireTemplate $copyQuestionnaireTemplate,
    ): RedirectResponse {
        $this->authorize('copy', $template);

        $copy = $copyQuestionnaireTemplate($template);

        return to_route('workspaces.templates.show', $copy);
    }
}
