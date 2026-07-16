<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\StoreDossierRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Queries\Dossiers\GetDossierCreateData;
use App\Queries\Dossiers\GetDossierIndexData;
use App\Queries\Dossiers\GetDossierShowData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use function is_string;

final class DossierController extends Controller
{
    public function index(Tenant $tenant, GetDossierIndexData $getDossierIndexData): Response
    {
        $this->authorize('viewAny', Dossier::class);

        return Inertia::render('dossiers/index', [
            'dossiers' => $getDossierIndexData(),
        ]);
    }

    public function create(Tenant $tenant, GetDossierCreateData $getDossierCreateData): Response
    {
        $this->authorize('create', Dossier::class);

        return Inertia::render('dossiers/create', [
            'clients' => $getDossierCreateData(),
        ]);
    }

    public function store(Tenant $tenant, StoreDossierRequest $request): RedirectResponse
    {
        $dossier = Dossier::query()->create($request->validated());

        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }

    public function show(
        Tenant $tenant,
        Request $request,
        Dossier $dossier,
        GetDossierShowData $getDossierShowData,
    ): Response {
        $this->authorize('view', $dossier);

        $data = $getDossierShowData($dossier);

        app(LogAuditActivity::class)(
            AuditEvent::DossierViewed,
            $dossier,
        );

        return Inertia::render('dossiers/show', [
            'access_grant_token' => $this->flashedAccessGrantToken($request),
            'access_grant_portal_url' => $this->flashedAccessGrantPortalUrl($request),
            ...$data,
        ]);
    }

    private function flashedAccessGrantToken(Request $request): ?string
    {
        $token = $request->session()->pull('access_grant_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function flashedAccessGrantPortalUrl(Request $request): ?string
    {
        $url = $request->session()->pull('access_grant_portal_url');

        return is_string($url) && $url !== '' ? $url : null;
    }
}
