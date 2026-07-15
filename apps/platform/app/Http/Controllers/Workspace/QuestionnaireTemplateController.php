<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Actions\Workspace\CopyQuestionnaireTemplate;
use App\Enums\QuestionnaireTemplateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreQuestionnaireTemplateRequest;
use App\Http\Requests\Workspace\UpdateQuestionnaireTemplateRequest;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class QuestionnaireTemplateController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', QuestionnaireTemplate::class);

        // oldest('name') instead of orderBy — Eloquent orderBy trips phpstan-strict-rules.
        $templates = QuestionnaireTemplate::queryVisibleToCurrentTenant()
            ->withCount('items')
            ->oldest('name')
            ->get();

        return Inertia::render('workspaces/templates/index', [
            'system_templates' => $templates
                ->filter(fn (QuestionnaireTemplate $template): bool => $template->isSystem())
                ->values()
                ->map(fn (QuestionnaireTemplate $template): array => $this->summary($template)),
            'firm_templates' => $templates
                ->filter(fn (QuestionnaireTemplate $template): bool => ! $template->isSystem())
                ->values()
                ->map(fn (QuestionnaireTemplate $template): array => $this->summary($template)),
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

        return Inertia::render('workspaces/templates/create', [
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

    public function show(QuestionnaireTemplate $template): Response
    {
        $this->authorize('view', $template);

        $template->load(['items' => fn ($query) => $query->oldest('sort_order')->oldest('id')]);

        return Inertia::render('workspaces/templates/show', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'category' => $template->category->value,
                'category_label' => $template->category->label(),
                'is_system' => $template->isSystem(),
                'items' => $template->items->map(fn (QuestionnaireTemplateItem $item): array => [
                    'id' => $item->id,
                    'type' => $item->type->value,
                    'title' => $item->title,
                    'instructions' => $item->instructions,
                    'sort_order' => $item->sort_order,
                ]),
            ],
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

    /**
     * @return array{id: int, name: string, description: string|null, category: string, category_label: string, items_count: int, is_system: bool}
     */
    private function summary(QuestionnaireTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category->value,
            'category_label' => $template->category->label(),
            'items_count' => $template->items_count,
            'is_system' => $template->isSystem(),
        ];
    }
}
