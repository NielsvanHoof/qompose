<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\ResolveClientAccessGrantFromTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Models\ClientAccessGrant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function max;
use function min;

final class ClientPortalSessionController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        ResolveClientAccessGrantFromTokenAction $resolveClientAccessGrantFromToken,
    ): RedirectResponse {
        $grant = $resolveClientAccessGrantFromToken->handle($token);

        if (! $grant instanceof ClientAccessGrant) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $lifetimeMinutes = max(1, min(
            60,
            (int) config('portal.session_lifetime_minutes', 15),
        ));
        $sessionExpiresAt = now()->addMinutes($lifetimeMinutes);

        if ($grant->expires_at->lessThan($sessionExpiresAt)) {
            $sessionExpiresAt = $grant->expires_at;
        }

        $request->session()->regenerate(destroy: true);
        $request->session()->put([
            ResolveClientPortalGrant::SESSION_GRANT_ID => $grant->getKey(),
            ResolveClientPortalGrant::SESSION_EXPIRES_AT => $sessionExpiresAt->getTimestamp(),
        ]);

        // StartSession stores successful GET URLs after the controller returns.
        // Prevent the redeemed bearer URL from being copied to `_previous.url`.
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        return to_route('portal.show');
    }
}
