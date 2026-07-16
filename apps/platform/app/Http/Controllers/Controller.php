<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    protected function workspaceRouteParameters(array $parameters = []): array
    {
        $tenant = request()->route('tenant');

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        return ['tenant' => $tenant, ...$parameters];
    }
}
