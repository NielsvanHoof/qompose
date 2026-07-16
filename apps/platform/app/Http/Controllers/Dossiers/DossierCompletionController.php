<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Dossiers\CompleteDossier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\CompleteDossierRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class DossierCompletionController extends Controller
{
    public function store(
        Tenant $tenant,
        CompleteDossierRequest $request,
        Dossier $dossier,
        CompleteDossier $completeDossier,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $completeDossier($dossier, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Dossier completed.',
        ]);

        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }
}
