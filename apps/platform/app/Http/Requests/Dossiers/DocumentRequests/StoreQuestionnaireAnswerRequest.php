<?php

declare(strict_types=1);

namespace App\Http\Requests\Dossiers\DocumentRequests;

use App\Enums\QuestionnaireItemType;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreQuestionnaireAnswerRequest extends FormRequest
{
    use LocalizesValidationAttributes;

    public function authorize(): bool
    {
        $dossier = $this->route('dossier');
        $documentRequest = $this->route('documentRequest');
        $user = $this->user();

        return $user !== null
            && $dossier instanceof Dossier
            && $documentRequest instanceof DocumentRequest
            && $user->can('update', $dossier)
            && $user->can('update', $documentRequest)
            && $documentRequest->type !== QuestionnaireItemType::File;
    }

    /** @return array<string, list<ValidationRule|string>> */
    public function rules(): array
    {
        $documentRequest = $this->route('documentRequest');

        if ($documentRequest instanceof DocumentRequest
            && $documentRequest->type === QuestionnaireItemType::Boolean) {
            return [
                'answer_boolean' => ['required', 'boolean'],
            ];
        }

        if ($documentRequest instanceof DocumentRequest
            && $documentRequest->type === QuestionnaireItemType::Date) {
            return [
                'answer_text' => ['required', 'date_format:Y-m-d'],
            ];
        }

        if ($documentRequest instanceof DocumentRequest
            && $documentRequest->type === QuestionnaireItemType::Number) {
            return [
                'answer_text' => ['required', 'numeric'],
            ];
        }

        return [
            'answer_text' => ['required', 'string', 'max:5000'],
        ];
    }
}
