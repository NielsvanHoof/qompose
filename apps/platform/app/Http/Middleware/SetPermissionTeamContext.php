<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function setPermissionsTeamId;

final class SetPermissionTeamContext
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            abort(Response::HTTP_FORBIDDEN);
        }

        setPermissionsTeamId($tenant->getKey());

        $user = $request->user();

        if ($user instanceof User) {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }

        return $next($request);
    }
}
