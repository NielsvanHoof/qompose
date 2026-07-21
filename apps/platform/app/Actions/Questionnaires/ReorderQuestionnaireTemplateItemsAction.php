<?php

declare(strict_types=1);

namespace App\Actions\Questionnaires;

use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use function count;

final class ReorderQuestionnaireTemplateItemsAction
{
    /**
     * @param  array<mixed>  $itemIds
     */
    public function handle(QuestionnaireTemplate $template, array $itemIds): void
    {
        $ids = array_map(
            static fn (mixed $id): int => (int) $id,
            $itemIds,
        );

        DB::transaction(function () use ($template, $ids): void {
            $templateQuery = QuestionnaireTemplate::query()->whereKey($template->getKey());
            $templateQuery->getQuery()->lockForUpdate();
            $lockedTemplate = $templateQuery->firstOrFail();

            $ownedIds = $lockedTemplate->items()
                ->toBase()
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            if (count($ids) !== count(array_unique($ids))
                || count($ids) !== count($ownedIds)
                || array_diff($ids, $ownedIds) !== []
                || array_diff($ownedIds, $ids) !== []) {
                throw ValidationException::withMessages([
                    'item_ids' => 'Submit every questionnaire template item exactly once.',
                ]);
            }

            foreach ($ids as $index => $id) {
                QuestionnaireTemplateItem::query()
                    ->whereKey($id)
                    ->update(['sort_order' => $index]);
            }
        });
    }
}
