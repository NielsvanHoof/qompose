<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreDocumentRequestRequest extends FormRequest
{
    /** @return array<string, list<ValidationRule|string>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
