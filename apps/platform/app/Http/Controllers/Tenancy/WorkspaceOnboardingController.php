<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Actions\Tenancy\ProvisionTenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\StoreWorkspaceRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class WorkspaceOnboardingController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('onboarding/firm');
    }

    public function store(
        StoreWorkspaceRequest $request,
        ProvisionTenant $provisionTenant,
    ): RedirectResponse {
        $name = $request->validated('name');
        $tenant = $provisionTenant->handle(
            $name,
            $request->authenticatedUser(),
        );

        $request->session()->put('active_tenant_id', $tenant->id);
        $request->session()->flash('inertia.refresh.workspaces', true);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Firm created. Add your first client to get started.',
        ]);

        return to_route('workspaces.clients.create', ['tenant' => $tenant]);
    }
}
