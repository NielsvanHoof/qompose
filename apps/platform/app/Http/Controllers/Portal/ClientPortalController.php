<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Models\ClientAccessGrant;
use App\Queries\Portal\GetClientPortalData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ClientPortalController extends Controller
{
    /**
     * Show the guest document portal for a valid access grant.
     */
    public function show(
        Request $request,
        string $token,
        GetClientPortalData $getClientPortalData,
    ): Response {
        $grant = $this->grantFromRequest($request);

        $grant->forceFill(['last_used_at' => now()])->save();

        return Inertia::render('portal/show', [
            'token' => $token,
            ...$getClientPortalData($grant),
        ]);
    }

    private function grantFromRequest(Request $request): ClientAccessGrant
    {
        $grant = $request->attributes->get(ResolveClientPortalGrant::REQUEST_ATTRIBUTE);

        if (! $grant instanceof ClientAccessGrant) {
            abort(404);
        }

        return $grant;
    }
}
