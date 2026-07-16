<?php

declare(strict_types=1);

namespace App\Http\Controllers\Questionnaires;

use App\Http\Controllers\Controller;
use App\Http\Requests\Questionnaires\ReorderQuestionnaireTemplateItemsRequest;
use App\Http\Requests\Questionnaires\StoreQuestionnaireTemplateItemRequest;
use App\Http\Requests\Questionnaires\UpdateQuestionnaireTemplateItemRequest;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use function count;

final class QuestionnaireTemplateItemController extends Controller
{
    public function store(
        StoreQuestionnaireTemplateItemRequest $request,
        QuestionnaireTemplate $template,
    ): RedirectResponse {
        // Aggregates live on the base query builder for strict PHPStan.
        $nextSortOrder = (int) $template->items()->toBase()->max('sort_order') + 1;

        $template->items()->create([
            ...$request->validated(),
            'sort_order' => $nextSortOrder,
        ]);

        return to_route('workspaces.templates.show', $template);
    }

    public function update(
        UpdateQuestionnaireTemplateItemRequest $request,
        QuestionnaireTemplate $template,
        QuestionnaireTemplateItem $item,
    ): RedirectResponse {
        abort_unless($item->questionnaire_template_id === $template->id, 404);

        $item->update($request->validated());

        return to_route('workspaces.templates.show', $template);
    }

    public function destroy(
        QuestionnaireTemplate $template,
        QuestionnaireTemplateItem $item,
    ): RedirectResponse {
        $this->authorize('update', $template);
        abort_unless($item->questionnaire_template_id === $template->id, 404);

        $item->delete();

        return to_route('workspaces.templates.show', $template);
    }

    public function reorder(
        ReorderQuestionnaireTemplateItemsRequest $request,
        QuestionnaireTemplate $template,
    ): RedirectResponse {
        $itemIds = array_map('intval', $request->validated('item_ids'));
        $ownedIds = $template->items()
            ->toBase()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        abort_unless(
            count($itemIds) === count($ownedIds)
            && array_diff($itemIds, $ownedIds) === [],
            422,
        );

        DB::transaction(function () use ($itemIds): void {
            foreach ($itemIds as $index => $itemId) {
                QuestionnaireTemplateItem::query()
                    ->whereKey($itemId)
                    ->update(['sort_order' => $index]);
            }
        });

        return to_route('workspaces.templates.show', $template);
    }
}
