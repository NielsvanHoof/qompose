<?php

declare(strict_types=1);

namespace App\Http\Requests\Dossiers;

use App\Enums\QuestionnaireItemType;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDocumentRequestRequest extends FormRequest
{
    /**
     * Require view access on the dossier plus permission to create document requests.
     */
    public function authorize(): bool
    {
        $dossier = $this->route('dossier');

        if (! $dossier instanceof Dossier) {
            return false;
        }

        $user = $this->user();

        return $user !== null
            && $user->can('view', $dossier)
            && $user->can('create', DocumentRequest::class);
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
