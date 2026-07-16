<?php

declare(strict_types=1);

namespace App\Http\Requests\Dossiers;

use App\Models\Dossier;
use Illuminate\Foundation\Http\FormRequest;

final class CompleteDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dossier = $this->route('dossier');

        return $dossier instanceof Dossier
            && ($this->user()?->can('complete', $dossier) ?? false);
    }

    /** @return array<string, never> */
    public function rules(): array
    {
        return [];
    }
}
