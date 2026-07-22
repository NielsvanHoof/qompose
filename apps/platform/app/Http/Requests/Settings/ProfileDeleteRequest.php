<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Concerns\EnsuresAuthenticatedUser;
use App\Concerns\PasswordValidationRules;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ProfileDeleteRequest extends FormRequest
{
    use EnsuresAuthenticatedUser, PasswordValidationRules;
    use LocalizesValidationAttributes;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => $this->currentPasswordRules(),
        ];
    }
}
