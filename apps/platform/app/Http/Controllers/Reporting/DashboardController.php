<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Queries\Reporting\FetchWorkspaceDashboardQuery;
use App\Queries\Tenancy\FetchActiveTenantMembershipsForUserQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class DashboardController extends Controller
{
    public function index(
        FetchActiveTenantMembershipsForUserQuery $getActiveTenantMembershipsForUser,
    ): RedirectResponse|Response {
        $user = request()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $memberships = $getActiveTenantMembershipsForUser->handle($user);

        if ($memberships->isEmpty()) {
            return to_route('onboarding.firm.create');
        }

        $tenant = Tenant::current();

        if ($tenant instanceof Tenant) {
            return to_route('workspaces.dashboard', ['tenant' => $tenant]);
        }

        return Inertia::render('dashboard', [
            'firms' => $memberships
                ->map(function (TenantMembership $membership): array {
                    $tenant = $this->resolveMembershipTenant($membership);

                    return [
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                    ];
                })
                ->all(),
        ]);
    }

    public function show(Tenant $tenant, FetchWorkspaceDashboardQuery $getWorkspaceDashboardData): Response
    {
        $this->authorize('viewAny', Dossier::class);

        return Inertia::render(
            'workspaces/dashboard',
            $getWorkspaceDashboardData->handle($tenant),
        );
    }

    private function resolveMembershipTenant(TenantMembership $membership): Tenant
    {
        $tenant = $membership->tenant;

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('Tenant membership is missing its tenant.');
        }

        return $tenant;
    }
}
