<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Actions\Tenancy\ProvisionTenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\StoreWorkspaceRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets an existing user create an additional firm (workspace).
 * First-run firm creation lives in WorkspaceOnboardingController.
 */
final class FirmController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('firms/create');
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

        // Switch the session to the new firm and refresh the
        // once-cached workspaces list in the sidebar switcher.
        $request->session()->put('active_tenant_id', $tenant->id);
        $request->session()->flash('inertia.refresh.workspaces', true);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Firm created. Add your first client to get started.',
        ]);

        return to_route('workspaces.clients.create', ['tenant' => $tenant]);
    }
}
