<?php

declare(strict_types=1);

namespace App\Http\Requests\Questionnaires;

use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use App\Models\QuestionnaireTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ReorderQuestionnaireTemplateItemsRequest extends FormRequest
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

    /** @return array<string, list<ValidationRule|string>> */
    public function rules(): array
    {
        return [
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'distinct'],
        ];
    }
}
