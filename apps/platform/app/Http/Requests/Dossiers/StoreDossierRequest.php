<?php

declare(strict_types=1);

namespace App\Http\Requests\Dossiers;

use App\Models\Dossier;
use App\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use Illuminate\Validation\Rule;
use RuntimeException;

final class StoreDossierRequest extends FormRequest
{
    use LocalizesValidationAttributes;
    /**
     * Authorize create via DossierPolicy so POST /dossiers cannot skip the create gate.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Dossier::class) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')
                    ->where('tenant_id', $this->tenantId()),
            ],
            'title' => ['required', 'string', 'max:255'],
            'reference' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique((new Dossier)->getTable(), 'reference')
                    ->where('tenant_id', $this->tenantId()),
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
