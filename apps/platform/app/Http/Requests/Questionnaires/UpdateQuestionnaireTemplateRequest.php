<?php

declare(strict_types=1);

namespace App\Http\Requests\Questionnaires;

use App\Enums\QuestionnaireTemplateCategory;
use App\Models\QuestionnaireTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use Illuminate\Validation\Rule;

final class UpdateQuestionnaireTemplateRequest extends FormRequest
{
    use LocalizesValidationAttributes;
    public function authorize(): bool
    {
        $template = $this->route('template');
        $user = $this->user();

        return $user !== null
            && $template instanceof QuestionnaireTemplate
            && $user->can('update', $template);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::enum(QuestionnaireTemplateCategory::class)],
        ];
    }
}
