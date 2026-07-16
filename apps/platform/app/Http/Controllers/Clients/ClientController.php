<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Models\Client;
use App\Models\Dossier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ClientController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Client::class);

        return Inertia::render('clients/index', [
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

    public function create(): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('clients/create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::query()->create($request->validated());

        if (Dossier::query()->toBase()->doesntExist()) {
            return to_route('workspaces.dossiers.create');
        }

        return to_route('workspaces.clients.index');
    }
}
