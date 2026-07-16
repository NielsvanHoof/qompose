<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InitializeTenantFromSession
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $tenantId = $request->session()->get('active_tenant_id');

        if ($tenantId === null) {
            // Prefer the model query builder over the relation for strict PHPStan.
            $tenantIds = TenantMembership::query()
                ->where('user_id', $user->id)
                ->where('status', TenantMembershipStatus::Active)
                ->pluck('tenant_id');

            if ($tenantIds->count() !== 1) {
                return $next($request);
            }

            $tenantId = $tenantIds->sole();
        }

        if (! is_int($tenantId) && ! ctype_digit((string) $tenantId)) {
            return $next($request);
        }

        $tenant = Tenant::query()
            ->whereKey($tenantId)
            ->whereHas('memberships', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', TenantMembershipStatus::Active))
            ->first();

        if (! $tenant instanceof Tenant) {
            $request->session()->forget('active_tenant_id');

            return $next($request);
        }

        $tenant->makeCurrent();
        $request->session()->put('active_tenant_id', $tenant->id);

        return $next($request);
    }
}
