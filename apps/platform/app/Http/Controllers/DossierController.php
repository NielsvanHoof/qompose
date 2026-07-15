<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Http\Requests\Workspace\StoreDossierRequest;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class DossierController extends Controller
{
    public function index(Tenant $tenant): Response
    {
        $this->authorize('viewAny', Dossier::class);

        $dossiers = Dossier::query()
            ->with('client:id,name')
            ->latest()
            ->get(['id', 'client_id', 'title', 'reference', 'status']);

        return Inertia::render('workspaces/dossiers/index', [
            'tenant' => ['slug' => $tenant->slug],
            'dossiers' => $dossiers->map(function (Dossier $dossier): array {
                $client = $this->resolveClient($dossier);

                return [
                    'id' => $dossier->id,
                    'client_name' => $client->name,
                    'title' => $dossier->title,
                    'reference' => $dossier->reference,
                    'status' => $dossier->status->value,
                ];
            }),
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        $this->authorize('create', Dossier::class);

        return Inertia::render('workspaces/dossiers/create', [
            'tenant' => ['slug' => $tenant->slug],
            'clients' => Client::query()
                ->get(['id', 'name', 'email'])
                ->sortBy('name')
                ->values()
                ->map(fn (Client $client): array => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                ]),
        ]);
    }

    public function store(StoreDossierRequest $request, Tenant $tenant): RedirectResponse
    {
        $dossier = Dossier::query()->create($request->validated());

        return to_route('workspaces.dossiers.show', [$tenant, $dossier->id]);
    }

    public function show(Tenant $tenant, int $dossier): Response
    {
        $record = Dossier::query()
            ->with([
                'client:id,name,email',
                'documentRequests' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->whereKey($dossier)
            ->firstOrFail();

        $this->authorize('view', $record);

        app(LogAuditActivity::class)(
            AuditEvent::DossierViewed,
            $record,
        );

        $client = $this->resolveClient($record);

        return Inertia::render('workspaces/dossiers/show', [
            'tenant' => ['slug' => $tenant->slug],
            'dossier' => [
                'id' => $record->id,
                'title' => $record->title,
                'reference' => $record->reference,
                'status' => $record->status->value,
                'client' => [
                    'name' => $client->name,
                    'email' => $client->email,
                ],
                'document_requests' => $record->documentRequests
                    ->map(fn ($documentRequest): array => [
                        'id' => $documentRequest->id,
                        'title' => $documentRequest->title,
                        'instructions' => $documentRequest->instructions,
                        'status' => $documentRequest->status->value,
                    ]),
            ],
        ]);
    }

    private function resolveClient(Dossier $dossier): Client
    {
        $client = $dossier->client;

        if (! $client instanceof Client) {
            throw new RuntimeException('Dossier client is missing.');
        }

        return $client;
    }
}
