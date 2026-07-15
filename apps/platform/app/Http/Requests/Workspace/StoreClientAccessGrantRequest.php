<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Models\ClientAccessGrant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreClientAccessGrantRequest extends FormRequest
{
    /**
     * Staff who can create dossiers may also issue client portal access grants.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ClientAccessGrant::class) ?? false;
    }

    /** @return array<string, list<ValidationRule|string>> */
    public function rules(): array
    {
        return [
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ];
    }
}
