<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\User;

trait EnsuresAuthenticatedUser
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    public function authenticatedUser(): User
    {
        $user = $this->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
