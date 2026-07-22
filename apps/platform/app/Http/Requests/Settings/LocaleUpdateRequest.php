<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Concerns\EnsuresAuthenticatedUser;
use App\Enums\Locale;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class LocaleUpdateRequest extends FormRequest
{
    use EnsuresAuthenticatedUser;
    use LocalizesValidationAttributes;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::enum(Locale::class)],
        ];
    }
}
