<?php

declare(strict_types=1);

namespace App\Http\Requests\Questionnaires;

use App\Enums\QuestionnaireTemplateCategory;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use App\Models\QuestionnaireTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreQuestionnaireTemplateRequest extends FormRequest
{
    use LocalizesValidationAttributes;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('create', QuestionnaireTemplate::class);
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
