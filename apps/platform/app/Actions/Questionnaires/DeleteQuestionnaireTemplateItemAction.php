<?php

declare(strict_types=1);

namespace App\Actions\Questionnaires;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;

/**
 * Delete a template item and write the matching audit event.
 */
final class DeleteQuestionnaireTemplateItemAction
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
    ) {}

    public function handle(
        QuestionnaireTemplate $template,
        QuestionnaireTemplateItem $item,
    ): void {
        $this->logAuditActivity->handle(
            AuditEvent::QuestionnaireTemplateItemDeleted,
            $item,
            [
                'title' => $item->title,
                'template_id' => $template->id,
            ],
        );

        $item->delete();
    }
}
