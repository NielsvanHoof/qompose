<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Concerns\EnsuresAuthenticatedUser;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Laravel\Fortify\InteractsWithTwoFactorState;

final class TwoFactorAuthenticationRequest extends FormRequest
{
    use EnsuresAuthenticatedUser, InteractsWithTwoFactorState;
    use LocalizesValidationAttributes;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
