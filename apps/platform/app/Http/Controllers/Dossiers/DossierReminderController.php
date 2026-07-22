<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Dossiers\SendDossierReminderAction;
use App\Enums\DossierReminderSource;
use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class DossierReminderController extends Controller
{
    public function store(
        Tenant $tenant,
        Dossier $dossier,
        SendDossierReminderAction $sendDossierReminder,
    ): RedirectResponse {
        $this->authorize('sendReminder', $dossier);

        $user = request()->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $sendDossierReminder->handle(
            $dossier,
            $user,
            DossierReminderSource::Manual,
            $user,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Client reminder queued with a fresh secure link.'),
        ]);

        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }
}
