<?php

declare(strict_types=1);

namespace App\Http\Requests\Dossiers;

use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use App\Models\Dossier;
use App\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RuntimeException;

final class UpdateDossierRequest extends FormRequest
{
    use LocalizesValidationAttributes;

    public function authorize(): bool
    {
        $dossier = $this->route('dossier');

        return $dossier instanceof Dossier
            && ($this->user()?->can('update', $dossier) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $dossier = $this->route('dossier');

        if (! $dossier instanceof Dossier) {
            throw new RuntimeException('Cannot update a dossier without route model binding.');
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'reference' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique((new Dossier)->getTable(), 'reference')
                    ->where('tenant_id', $this->tenantId())
                    ->ignore($dossier),
            ],
        ];
    }

    private function tenantId(): int
    {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('Cannot validate a dossier without an active tenant.');
        }

        return $tenant->id;
    }
}
