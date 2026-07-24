<?php

declare(strict_types=1);

namespace App\Actions\Questionnaires;

use App\Models\QuestionnaireTemplateItem;

/**
 * Persist updated questionnaire template item attributes.
 */
final class UpdateQuestionnaireTemplateItemAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(QuestionnaireTemplateItem $item, array $attributes): void
    {
        $item->update($attributes);
    }
}
