<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureValidTenantMembership
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = Tenant::current();

        if ($user === null || ! $tenant instanceof Tenant) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if (! $user->belongsToTenant($tenant)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        foreach ($user->tenantMemberships as $membership) {
            if ($membership->tenant_id === $tenant->getKey()) {
                $membership->update(['last_accessed_at' => now()]);

                break;
            }
        }

        return $next($request);
    }
}
