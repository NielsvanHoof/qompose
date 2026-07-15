<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreClientRequest;
use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ClientController extends Controller
{
    public function index(Tenant $tenant): Response
    {
        $this->authorize('viewAny', Client::class);

        return Inertia::render('workspaces/clients/index', [
            'tenant' => ['slug' => $tenant->slug],
            'clients' => Client::query()
                ->withCount('dossiers')
                ->get(['id', 'name', 'email'])
                ->sortBy('name')
                ->values()
                ->map(fn (Client $client): array => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'dossiers_count' => $client->dossiers_count,
                ]),
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('workspaces/clients/create', [
            'tenant' => ['slug' => $tenant->slug],
        ]);
    }

    public function store(StoreClientRequest $request, Tenant $tenant): RedirectResponse
    {
        Client::query()->create($request->validated());

        return to_route('workspaces.clients.index', $tenant);
    }
}
