<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RuntimeException;

final class StoreClientRequest extends FormRequest
{
    /**
     * Authorize create via ClientPolicy so store cannot bypass the create screen check.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Client::class) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique((new Client)->getTable(), 'email')
                    ->where('tenant_id', $this->tenantId()),
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
