<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Queries\Portal\FetchClientPortalQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        LogAuditActivityAction $logAuditActivity,
    ): Response {
        $grant = $this->grantFromRequest($request);

        DB::transaction(function () use ($grant, $logAuditActivity): void {
            $openedAt = now();
            $grant->forceFill(['last_used_at' => $openedAt])->save();

            Dossier::query()
                ->whereKey($grant->dossier_id)
                ->toBase()
                ->update(['last_client_opened_at' => $openedAt]);

            $logAuditActivity->handle(
                AuditEvent::ClientPortalAccessed,
                $grant,
                [
                    'source' => 'client_portal',
                    'dossier_id' => $grant->dossier_id,
                ],
            );
        });

        return Inertia::render('portal/show', [
            ...$getClientPortalData->handle($grant),
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
