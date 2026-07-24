<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\RecordClientPortalAccessAction;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Models\ClientAccessGrant;
use App\Queries\Portal\FetchClientPortalQuery;
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
        FetchClientPortalQuery $getClientPortalData,
        RecordClientPortalAccessAction $recordClientPortalAccess,
    ): Response {
        $grant = $this->grantFromRequest($request);

        // Side effects (grant touch, dossier stamp, audit) live in the Action.
        $recordClientPortalAccess->handle($grant);

        return Inertia::render('portal/show', $getClientPortalData->handle($grant));
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
