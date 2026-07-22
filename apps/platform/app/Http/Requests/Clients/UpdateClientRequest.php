<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RuntimeException;

final class UpdateClientRequest extends FormRequest
{
    use LocalizesValidationAttributes;

    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            && ($this->user()?->can('update', $client) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $client = $this->route('client');

        if (! $client instanceof Client) {
            throw new RuntimeException('Cannot update a client without route model binding.');
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique((new Client)->getTable(), 'email')
                    ->where('tenant_id', $this->tenantId())
                    ->ignore($client),
            ],
        ];
    }

    private function tenantId(): int
    {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('Cannot validate a client without an active tenant.');
        }

        return $tenant->id;
    }
}
