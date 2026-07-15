<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Enums\TenantMembershipStatus;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(): RedirectResponse|Response
    {
        $user = request()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $memberships = $user->tenantMemberships()
            ->with('tenant:id,name,slug')
            ->where('status', TenantMembershipStatus::Active)
            ->get()
            ->sortBy(fn (TenantMembership $membership): string => $membership->tenant->name)
            ->values();

        if ($memberships->isEmpty()) {
            return to_route('onboarding.firm.create');
        }

        $tenant = Tenant::current();

        if ($tenant instanceof Tenant) {
            return $this->showDashboard($tenant);
        }

        return Inertia::render('dashboard', [
            'firms' => $memberships->map(fn (TenantMembership $membership): array => [
                'name' => $membership->tenant->name,
                'slug' => $membership->tenant->slug,
            ]),
        ]);
    }

    private function showDashboard(Tenant $tenant): Response
    {
        $this->authorize('viewAny', Dossier::class);

        $recentDossiers = Dossier::query()
            ->with('client:id,name')
            ->latest()
            ->limit(5)
            ->get(['id', 'client_id', 'title', 'reference', 'status', 'updated_at']);

        return Inertia::render('workspaces/dashboard', [
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'metrics' => [
                'clients' => Client::query()->count(),
                'open_dossiers' => Dossier::query()
                    ->whereNot('status', DossierStatus::Completed)
                    ->count(),
                'awaiting_client' => Dossier::query()
                    ->where('status', DossierStatus::AwaitingClient)
                    ->count(),
                'in_review' => Dossier::query()
                    ->where('status', DossierStatus::InReview)
                    ->count(),
                'outstanding_document_requests' => DocumentRequest::query()
                    ->whereIn('status', [
                        DocumentRequestStatus::Pending,
                        DocumentRequestStatus::Rejected,
                    ])
                    ->count(),
            ],
            'recent_dossiers' => $recentDossiers->map(fn (Dossier $dossier): array => [
                'id' => $dossier->id,
                'title' => $dossier->title,
                'reference' => $dossier->reference,
                'status' => $dossier->status->value,
                'client_name' => $dossier->client->name,
                'updated_at' => $dossier->updated_at->toDateTimeString(),
            ]),
        ]);
    }
}
