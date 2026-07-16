<?php

declare(strict_types=1);

namespace App\Actions\Questionnaires;

use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use Illuminate\Support\Facades\DB;

final class CreateQuestionnaireTemplateItem
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(
        QuestionnaireTemplate $template,
        array $attributes,
    ): QuestionnaireTemplateItem {
        return DB::transaction(function () use ($template, $attributes): QuestionnaireTemplateItem {
            $templateQuery = QuestionnaireTemplate::query()->whereKey($template->getKey());
            $templateQuery->getQuery()->lockForUpdate();
            $lockedTemplate = $templateQuery->firstOrFail();

            $nextSortOrder = (int) $lockedTemplate
                ->items()
                ->toBase()
                ->max('sort_order') + 1;

            return $lockedTemplate->items()->create([
                ...$attributes,
                'sort_order' => $nextSortOrder,
            ]);
        });
    }
}
