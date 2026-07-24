<?php

declare(strict_types=1);

namespace App\Actions\Questionnaires;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Models\QuestionnaireTemplate;

/**
 * Soft-delete a questionnaire template and write the matching audit event.
 */
final class DeleteQuestionnaireTemplateAction
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
    ) {}

    public function handle(QuestionnaireTemplate $template): void
    {
        $this->logAuditActivity->handle(
            AuditEvent::QuestionnaireTemplateDeleted,
            $template,
            [
                'name' => $template->name,
                'category' => $template->category->value,
            ],
        );

        $template->delete();
    }
}
