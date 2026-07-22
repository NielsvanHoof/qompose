<?php

declare(strict_types=1);

namespace App\Http\Requests\Dossiers;

use App\Enums\QuestionnaireItemType;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDocumentRequestRequest extends FormRequest
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
            && $user->can('update', $documentRequest);
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
