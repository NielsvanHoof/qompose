<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Enums\Permission;
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
            'dossiers' => $getDossierIndexData->handle(),
            // Current Spatie query-string values for the shared IndexQuery UI.
            'filters' => request()->input('filter', []),
            'sort' => request()->query('sort'),
            // Toolbar metadata for shared IndexQuery UI (filters / sorts / defaults).
            'indexQuery' => [
                'filters' => [
                    ['key' => 'q', 'type' => 'search', 'label' => __('Search')],
                    [
                        'key' => 'status',
                        'type' => 'select',
                        'label' => __('Status'),
                        'options' => [
                            ['value' => 'draft', 'label' => __('Draft')],
                            ['value' => 'awaiting_client', 'label' => __('Awaiting client')],
                            ['value' => 'in_review', 'label' => __('In review')],
                            ['value' => 'completed', 'label' => __('Completed')],
                        ],
                    ],
                    ['key' => 'client', 'type' => 'search', 'label' => __('Client')],
                ],
                'sorts' => [
                    ['key' => '-updated_at', 'label' => __('Recently updated')],
                    ['key' => 'updated_at', 'label' => __('Oldest updated')],
                    ['key' => 'title', 'label' => __('Title (A–Z)')],
                    ['key' => '-title', 'label' => __('Title (Z–A)')],
                    ['key' => 'status', 'label' => __('Status (A–Z)')],
                    ['key' => '-created_at', 'label' => __('Newest first')],
                    ['key' => 'created_at', 'label' => __('Oldest first')],
                ],
                'defaults' => [
                    'sort' => '-updated_at',
                    'per_page' => 15,
                ],
            ],
        ]);
    }

    public function create(Tenant $tenant, GetDossierCreateData $getDossierCreateData): Response
    {
        $this->authorize('create', Dossier::class);

        return Inertia::render('dossiers/create', [
            'clients' => $getDossierCreateData->handle(),
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
        LogAuditActivity $logAuditActivity,
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
