<?php

declare(strict_types=1);

namespace App\Http\Requests\Dossiers\Builder;

use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ReorderDocumentRequestsRequest extends FormRequest
{
    use LocalizesValidationAttributes;

    public function authorize(): bool
    {
        $dossier = $this->route('dossier');
        $user = $this->user();

        return $user !== null
            && $dossier instanceof Dossier
            && $user->can('update', $dossier)
            && $user->can('create', DocumentRequest::class);
    }

    /** @return array<string, list<ValidationRule|string>> */
    public function rules(): array
    {
        return [
            'document_request_ids' => ['required', 'array', 'min:1'],
            'document_request_ids.*' => ['integer', 'distinct'],
        ];
    }
}
