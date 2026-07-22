<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Audit\LogAuditActivityAction;
use App\Actions\Dossiers\CreateDossierAction;
use App\Actions\Dossiers\DeleteDossierAction;
use App\Actions\Dossiers\RestoreDossierAction;
use App\Actions\Dossiers\UpdateDossierAction;
use App\Data\Shared\PersonOptionData;
use App\Enums\AuditEvent;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\StoreDossierRequest;
use App\Http\Requests\Dossiers\UpdateDossierRequest;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use App\Queries\Dossiers\FetchArchivedDossiersQuery;
use App\Queries\Dossiers\FetchDossierCreateQuery;
use App\Queries\Dossiers\FetchDossierIndexQuery;
use App\Queries\Dossiers\FetchDossierShowQuery;
use App\Queries\Tenancy\FetchResponsibleStaffOptionsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
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

    public function create(
        Tenant $tenant,
        FetchDossierCreateQuery $getDossierCreateData,
        FetchResponsibleStaffOptionsQuery $fetchResponsibleStaffOptions,
    ): Response {
        $this->authorize('create', Dossier::class);

        return Inertia::render('dossiers/create', [
            'clients' => array_map(
                static fn (PersonOptionData $client): array => $client->toArray(),
                $getDossierCreateData->handle(),
            ),
            'responsible_staff' => array_map(
                static fn (PersonOptionData $staff): array => $staff->toArray(),
                $fetchResponsibleStaffOptions->handle($tenant),
            ),
        ]);
    }

    public function archived(
        Tenant $tenant,
        FetchArchivedDossiersQuery $fetchArchivedDossiers,
        Request $request,
    ): Response {
        $this->authorize('viewAny', Dossier::class);

        return Inertia::render('dossiers/archived', [
            'dossiers' => $fetchArchivedDossiers->handle(),
            ...$fetchArchivedDossiers->indexQueryProps(),
            'can_restore' => $request->user()?->can(Permission::CreateDossiers->value) ?? false,
            'can_restore_clients' => $request->user()?->can(Permission::ManageClients->value) ?? false,
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
            'can_edit' => $request->user()?->can('update', $dossier) ?? false,
            'can_send_reminder' => $request->user()?->can('sendReminder', $dossier) ?? false,
            'can_review' => $request->user()?->can(Permission::ReviewDocuments->value) ?? false,
            'can_download' => $request->user()?->can(Permission::DownloadDocuments->value) ?? false,
            ...$data->toArray(),
        ]);
    }

    public function edit(
        Tenant $tenant,
        Dossier $dossier,
        FetchResponsibleStaffOptionsQuery $fetchResponsibleStaffOptions,
    ): Response {
        $this->authorize('update', $dossier);

        $dossier->load('client:id,name,email');
        $client = $dossier->client;

        if (! $client instanceof Client) {
            throw new RuntimeException('Dossier client is missing.');
        }

        return Inertia::render('dossiers/edit', [
            'dossier' => [
                'id' => $dossier->id,
                'title' => $dossier->title,
                'reference' => $dossier->reference,
                'due_date' => $dossier->due_date?->toDateString(),
                'responsible_user_id' => $dossier->responsible_user_id,
                'reminder_interval_days' => $dossier->reminder_interval_days,
                'client' => [
                    'name' => $client->name,
                    'email' => $client->email,
                ],
            ],
            'responsible_staff' => array_map(
                static fn (PersonOptionData $staff): array => $staff->toArray(),
                $fetchResponsibleStaffOptions->handle($tenant),
            ),
        ]);
    }

    public function update(
        Tenant $tenant,
        UpdateDossierRequest $request,
        Dossier $dossier,
        UpdateDossierAction $updateDossier,
    ): RedirectResponse {
        $updateDossier->handle($dossier, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Dossier details updated.'),
        ]);

        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
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

    public function restore(
        Tenant $tenant,
        Dossier $dossier,
        RestoreDossierAction $restoreDossier,
    ): RedirectResponse {
        $this->authorize('restore', $dossier);

        $user = request()->user();

        if (! $user instanceof User) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        $restoreDossier->handle($dossier, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Dossier restored. Create a new portal link before inviting the client again.'),
        ]);

        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
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
