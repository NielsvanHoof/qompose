<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Queries\Clients\GetClientIndexData;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ClientController extends Controller
{
    public function index(Tenant $tenant, GetClientIndexData $getClientIndexData): Response
    {
        $this->authorize('viewAny', Client::class);

        return Inertia::render('clients/index', [
            'clients' => $getClientIndexData(),
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('clients/create');
    }

    public function store(Tenant $tenant, StoreClientRequest $request): RedirectResponse
    {
        Client::query()->create($request->validated());

        if (Dossier::query()->toBase()->doesntExist()) {
            return to_route(
                'workspaces.dossiers.create',
                $this->workspaceRouteParameters(),
            );
        }

        return to_route(
            'workspaces.clients.index',
            $this->workspaceRouteParameters(),
        );
    }
}
