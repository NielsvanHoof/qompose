<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\DeleteClientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use App\Queries\Clients\FetchClientIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ClientController extends Controller
{
    public function index(Tenant $tenant, FetchClientIndexQuery $fetchClientIndex, Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        return Inertia::render('clients/index', [
            'clients' => $fetchClientIndex->handle(),
            ...$fetchClientIndex->indexQueryProps(),
            'can_manage' => $request->user()?->can('create', Client::class) ?? false,
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

    public function destroy(
        Tenant $tenant,
        Client $client,
        DeleteClientAction $deleteClient,
    ): RedirectResponse {
        $this->authorize('delete', $client);

        $user = request()->user();

        if (! $user instanceof User) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        $deleteClient->handle($client, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Client archived.'),
        ]);

        return to_route(
            'workspaces.clients.index',
            $this->workspaceRouteParameters(),
        );
    }
}
