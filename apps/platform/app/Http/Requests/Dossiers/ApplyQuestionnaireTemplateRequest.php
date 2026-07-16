<?php

declare(strict_types=1);

namespace App\Http\Requests\Dossiers;

use App\Models\Dossier;
use App\Models\QuestionnaireTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ApplyQuestionnaireTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dossier = $this->route('dossier');
        $user = $this->user();

        if ($user === null || ! $dossier instanceof Dossier || ! $user->can('view', $dossier)) {
            return false;
        }

        $templateId = $this->input('questionnaire_template_id');

        if (! is_numeric($templateId)) {
            return false;
        }

        $template = QuestionnaireTemplate::queryVisibleToCurrentTenant()
            ->find((int) $templateId);

        return $template instanceof QuestionnaireTemplate
            && $user->can('apply', $template);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'questionnaire_template_id' => [
                'required',
                'integer',
                Rule::exists('questionnaire_templates', 'id'),
            ],
        ];
    }
}
