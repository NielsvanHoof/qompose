<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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

    /** @return array<string, list<ValidationRule|string>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
