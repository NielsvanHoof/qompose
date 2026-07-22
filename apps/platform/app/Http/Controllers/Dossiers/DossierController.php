<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Audit\LogAuditActivityAction;
use App\Actions\Dossiers\CreateDossierAction;
use App\Actions\Dossiers\DeleteDossierAction;
use App\Enums\AuditEvent;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\StoreDossierRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use App\Queries\Dossiers\FetchDossierCreateQuery;
use App\Queries\Dossiers\FetchDossierIndexQuery;
use App\Queries\Dossiers\FetchDossierShowQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

use function is_string;

final class DossierController extends Controller
{
    public function index(Tenant $tenant, FetchDossierIndexQuery $fetchDossierIndex): Response
    {
        $this->authorize('viewAny', Dossier::class);

        return Inertia::render('dossiers/index', [
            'dossiers' => $fetchDossierIndex->handle(),
            ...$fetchDossierIndex->indexQueryProps(),
        ]);
    }

    public function create(Tenant $tenant, FetchDossierCreateQuery $getDossierCreateData): Response
    {
        $this->authorize('create', Dossier::class);

        return Inertia::render('dossiers/create', [
            'clients' => $getDossierCreateData->handle(),
        ]);
    }

    public function store(
        Tenant $tenant,
        StoreDossierRequest $request,
        CreateDossierAction $createDossier,
    ): RedirectResponse {
        $dossier = $createDossier->handle($request->validated());

        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }

    public function show(
        Tenant $tenant,
        Request $request,
        Dossier $dossier,
        FetchDossierShowQuery $getDossierShowData,
        LogAuditActivityAction $logAuditActivity,
    ): Response {
        $this->authorize('view', $dossier);

        $data = $getDossierShowData->handle($dossier);

        $logAuditActivity->handle(
            AuditEvent::DossierViewed,
            $dossier,
        );

        return Inertia::render('dossiers/show', [
            'access_grant_token' => $this->flashedAccessGrantToken($request),
            'access_grant_portal_url' => $this->flashedAccessGrantPortalUrl($request),
            'can_manage' => $request->user()?->can(Permission::CreateDossiers->value) ?? false,
            'can_review' => $request->user()?->can(Permission::ReviewDocuments->value) ?? false,
            'can_download' => $request->user()?->can(Permission::DownloadDocuments->value) ?? false,
            ...$data,
        ]);
    }

    public function destroy(
        Tenant $tenant,
        Dossier $dossier,
        DeleteDossierAction $deleteDossier,
    ): RedirectResponse {
        $this->authorize('delete', $dossier);

        $user = request()->user();

        if (! $user instanceof User) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        $deleteDossier->handle($dossier, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Dossier archived.'),
        ]);

        return to_route(
            'workspaces.dossiers.index',
            $this->workspaceRouteParameters(),
        );
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
