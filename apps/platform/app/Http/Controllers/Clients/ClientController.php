<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\DeleteClientAction;
use App\Actions\Clients\RestoreClientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use App\Queries\Clients\FetchArchivedClientsQuery;
use App\Queries\Clients\FetchClientIndexQuery;
use App\Queries\Clients\FetchClientShowQuery;
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

    public function archived(
        Tenant $tenant,
        FetchArchivedClientsQuery $fetchArchivedClients,
        Request $request,
    ): Response {
        $this->authorize('viewAny', Client::class);

        return Inertia::render('clients/archived', [
            'clients' => $fetchArchivedClients->handle(),
            ...$fetchArchivedClients->indexQueryProps(),
            'can_restore' => $request->user()?->can('create', Client::class) ?? false,
        ]);
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

    public function show(
        Tenant $tenant,
        Client $client,
        FetchClientShowQuery $fetchClientShow,
    ): Response {
        $this->authorize('view', $client);

        return Inertia::render('clients/show', $fetchClientShow->handle($client));
    }

    public function edit(Tenant $tenant, Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('clients/edit', [
            'client' => $client->only(['id', 'name', 'email']),
        ]);
    }

    public function update(
        Tenant $tenant,
        UpdateClientRequest $request,
        Client $client,
    ): RedirectResponse {
        $client->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Client details updated.'),
        ]);

        return to_route(
            'workspaces.clients.show',
            $this->workspaceRouteParameters(['client' => $client]),
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

    public function restore(
        Tenant $tenant,
        Client $client,
        RestoreClientAction $restoreClient,
    ): RedirectResponse {
        $this->authorize('restore', $client);

        $user = request()->user();

        if (! $user instanceof User) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        $restoreClient->handle($client, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Client restored. Archived dossiers remain in the dossier archive.'),
        ]);

        return to_route(
            'workspaces.clients.show',
            $this->workspaceRouteParameters(['client' => $client]),
        );
    }
}
