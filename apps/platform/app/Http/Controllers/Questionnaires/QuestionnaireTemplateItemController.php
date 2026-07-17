<?php

declare(strict_types=1);

namespace App\Http\Controllers\Questionnaires;

use App\Actions\Questionnaires\CreateQuestionnaireTemplateItem;
use App\Actions\Questionnaires\ReorderQuestionnaireTemplateItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Questionnaires\ReorderQuestionnaireTemplateItemsRequest;
use App\Http\Requests\Questionnaires\StoreQuestionnaireTemplateItemRequest;
use App\Http\Requests\Questionnaires\UpdateQuestionnaireTemplateItemRequest;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;

final class QuestionnaireTemplateItemController extends Controller
{
    public function store(
        Tenant $tenant,
        StoreQuestionnaireTemplateItemRequest $request,
        QuestionnaireTemplate $template,
        CreateQuestionnaireTemplateItem $createQuestionnaireTemplateItem,
    ): RedirectResponse {
        $createQuestionnaireTemplateItem->handle($template, $request->validated());

        return to_route(
            'workspaces.templates.show',
            $this->workspaceRouteParameters(['template' => $template]),
        );
    }

    public function update(
        Tenant $tenant,
        UpdateQuestionnaireTemplateItemRequest $request,
        QuestionnaireTemplate $template,
        QuestionnaireTemplateItem $item,
    ): RedirectResponse {
        $item->update($request->validated());

        return to_route(
            'workspaces.templates.show',
            $this->workspaceRouteParameters(['template' => $template]),
        );
    }

    public function destroy(
        Tenant $tenant,
        QuestionnaireTemplate $template,
        QuestionnaireTemplateItem $item,
    ): RedirectResponse {
        $this->authorize('update', $template);

        $item->delete();

        return to_route(
            'workspaces.templates.show',
            $this->workspaceRouteParameters(['template' => $template]),
        );
    }

    public function reorder(
        Tenant $tenant,
        ReorderQuestionnaireTemplateItemsRequest $request,
        QuestionnaireTemplate $template,
        ReorderQuestionnaireTemplateItems $reorderQuestionnaireTemplateItems,
    ): RedirectResponse {
        $reorderQuestionnaireTemplateItems->handle(
            $template,
            $request->array('item_ids'),
        );

        return to_route(
            'workspaces.templates.show',
            $this->workspaceRouteParameters(['template' => $template]),
        );
    }
}
