<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\User;
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
        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $tenantSlug = $tenantParameter instanceof Tenant
            ? $tenantParameter->slug
            : (is_string($tenantParameter) ? $tenantParameter : null);

        $tenant = $tenantSlug === null
            ? null
            : Tenant::query()
                ->where('slug', $tenantSlug)
                ->whereHas('memberships', fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', TenantMembershipStatus::Active))
                ->first();

        if (! $tenant instanceof Tenant) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $tenant->makeCurrent();
        $request->route()?->setParameter('tenant', $tenant);
        $request->session()->put('active_tenant_id', $tenant->id);

        return $next($request);
    }
}
