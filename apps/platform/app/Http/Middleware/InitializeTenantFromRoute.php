<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function is_string;

final class InitializeTenantFromRoute
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantParameter = $request->route('tenant');

        if ($tenantParameter instanceof Tenant) {
            $tenant = $tenantParameter;
        } elseif (is_string($tenantParameter)) {
            $tenant = Tenant::query()->where('slug', $tenantParameter)->first();
        } else {
            $tenant = null;
        }

        if (! $tenant instanceof Tenant) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $tenant->makeCurrent();
        $request->route()?->setParameter('tenant', $tenant);

        return $next($request);
    }
}
