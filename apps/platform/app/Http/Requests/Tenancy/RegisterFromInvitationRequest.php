<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenancy;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterFromInvitationRequest extends FormRequest
{
    use LocalizesValidationAttributes;
    use PasswordValidationRules;
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * @return array{name: string, email: string, password: string, password_confirmation: string}
     */
    public function registrationInput(): array
    {
        return [
            'name' => (string) $this->validated('name'),
            'email' => (string) $this->validated('email'),
            'password' => (string) $this->validated('password'),
            // confirmed rule checks this field but does not include it in validated().
            'password_confirmation' => (string) $this->input('password_confirmation'),
        ];
    }
}
