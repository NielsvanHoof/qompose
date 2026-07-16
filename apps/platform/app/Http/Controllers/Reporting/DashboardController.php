<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reporting;

use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Enums\TenantMembershipStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class DashboardController extends Controller
{
    public function index(): RedirectResponse|Response
    {
        $user = request()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        // Query the model builder directly so strict PHPStan accepts where()/with().
        $memberships = TenantMembership::query()
            ->with('tenant:id,name,slug')
            ->where('user_id', $user->id)
            ->where('status', TenantMembershipStatus::Active)
            ->get()
            ->sortBy(fn (TenantMembership $membership): string => $this->resolveMembershipTenant($membership)->name)
            ->values();

        if ($memberships->isEmpty()) {
            return to_route('onboarding.firm.create');
        }

        $tenant = Tenant::current();

        if ($tenant instanceof Tenant) {
            return $this->showDashboard($tenant);
        }

        return Inertia::render('dashboard', [
            'firms' => $memberships->map(function (TenantMembership $membership): array {
                $tenant = $this->resolveMembershipTenant($membership);

                return [
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                ];
            }),
        ]);
    }

    private function showDashboard(Tenant $tenant): Response
    {
        $this->authorize('viewAny', Dossier::class);

        $recentDossiersQuery = Dossier::query()
            ->with('client:id,name')
            ->latest();

        // limit() lives on the base query builder; set it there for strict PHPStan.
        $recentDossiersQuery->getQuery()->limit(5);

        $recentDossiers = $recentDossiersQuery
            ->get(['id', 'client_id', 'title', 'reference', 'status', 'updated_at']);

        return Inertia::render('workspaces/dashboard', [
            'metrics' => [
                'clients' => Client::query()->toBase()->count(),
                'open_dossiers' => Dossier::query()
                    ->whereNot('status', DossierStatus::Completed)
                    ->toBase()
                    ->count(),
                'awaiting_client' => Dossier::query()
                    ->where('status', DossierStatus::AwaitingClient)
                    ->toBase()
                    ->count(),
                'in_review' => Dossier::query()
                    ->where('status', DossierStatus::InReview)
                    ->toBase()
                    ->count(),
                'outstanding_document_requests' => DocumentRequest::query()
                    ->where('status', DocumentRequestStatus::Pending)
                    ->orWhere('status', DocumentRequestStatus::Rejected)
                    ->toBase()
                    ->count(),
            ],
            'recent_dossiers' => $recentDossiers->map(function (Dossier $dossier): array {
                $client = $dossier->client;

                if (! $client instanceof Client) {
                    throw new RuntimeException('Dossier client is missing.');
                }

                return [
                    'id' => $dossier->id,
                    'title' => $dossier->title,
                    'reference' => $dossier->reference,
                    'status' => $dossier->status->value,
                    'client_name' => $client->name,
                    'updated_at' => $dossier->updated_at->toDateTimeString(),
                ];
            }),
        ]);
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
