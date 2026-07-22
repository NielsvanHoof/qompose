<?php

declare(strict_types=1);

namespace App\Http\Requests\Questionnaires;

use App\Enums\QuestionnaireItemType;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use Illuminate\Validation\Rule;

final class UpdateQuestionnaireTemplateItemRequest extends FormRequest
{
    use LocalizesValidationAttributes;
    public function authorize(): bool
    {
        $template = $this->route('template');
        $item = $this->route('item');
        $user = $this->user();

        return $user !== null
            && $template instanceof QuestionnaireTemplate
            && $item instanceof QuestionnaireTemplateItem
            && $user->can('update', $template);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(QuestionnaireItemType::class)],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
