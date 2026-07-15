<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    protected function currentTenant(): Tenant
    {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            abort(403);
        }

        return $tenant;
    }
}
