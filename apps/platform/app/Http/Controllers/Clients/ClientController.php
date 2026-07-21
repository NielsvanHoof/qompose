<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Clients\CreateClientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Queries\Clients\FetchClientIndexQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ClientController extends Controller
{
    public function index(Tenant $tenant, FetchClientIndexQuery $fetchClientIndex): Response
    {
        $this->authorize('viewAny', Client::class);

        return Inertia::render('clients/index', [
            'clients' => $fetchClientIndex->handle(),
            ...$fetchClientIndex->indexQueryProps(),
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('clients/create');
    }

    public function store(
        Tenant $tenant,
        StoreClientRequest $request,
        CreateClientAction $createClient,
    ): RedirectResponse {
        $createClient->handle($request->validated());

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
