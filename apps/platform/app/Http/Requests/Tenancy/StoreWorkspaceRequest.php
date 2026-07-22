<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenancy;

use App\Concerns\EnsuresAuthenticatedUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;

final class StoreWorkspaceRequest extends FormRequest
{
    use LocalizesValidationAttributes;
    use EnsuresAuthenticatedUser;

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
        ];
    }
}
