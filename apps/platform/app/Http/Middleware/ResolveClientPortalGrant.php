<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Portal\ResolveClientAccessGrantFromToken;
use App\Models\ClientAccessGrant;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the portal magic-link token and makes the grant's tenant current
 * before route model binding runs (see bootstrap/app.php priority list).
 */
final class ResolveClientPortalGrant
{
    public const string REQUEST_ATTRIBUTE = 'client_access_grant';

    public function __construct(
        private ResolveClientAccessGrantFromToken $resolveClientAccessGrantFromToken,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only portal routes carry a magic-link token.
        if (! $request->routeIs('portal.*')) {
            return $next($request);
        }

        $plainTextToken = (string) $request->route('token', '');

        $grant = $this->resolveClientAccessGrantFromToken->handle($plainTextToken);

        if (! $grant instanceof ClientAccessGrant) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $tenant = Tenant::query()->find($grant->tenant_id);

        if (! $tenant instanceof Tenant) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Tenant must be current so BelongsToTenant scopes work for dossier queries.
        $tenant->makeCurrent();

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $grant);

        return $next($request);
    }
}
