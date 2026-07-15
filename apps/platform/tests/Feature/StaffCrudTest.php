<?php

declare(strict_types=1);

use App\Actions\Tenants\ProvisionTenant;
use App\Enums\AuditEvent;
use App\Enums\Role;
use App\Models\Activity;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('staff can create a client, dossier, and document request', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $this->actingAs($owner)
        ->get(route('workspaces.clients.index', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/clients/index')
            ->has('clients', 0));

    $this->post(route('workspaces.clients.store', $tenant), [
        'name' => 'Jane Client',
        'email' => 'jane@example.com',
    ])->assertRedirect(route('workspaces.clients.index', $tenant));

    $tenant->makeCurrent();
    $client = Client::query()->sole();

    $this->post(route('workspaces.dossiers.store', $tenant), [
        'client_id' => $client->id,
        'title' => '2025 Payroll dossier',
        'reference' => 'PAY-2025-001',
    ])->assertRedirect();

    $dossier = Dossier::query()->sole();

    $this->post(route('workspaces.dossiers.document-requests.store', [$tenant, $dossier]), [
        'title' => 'Payslip January 2025',
        'instructions' => 'Upload the original PDF.',
    ])->assertRedirect(route('workspaces.dossiers.show', [$tenant, $dossier]));

    $this->get(route('workspaces.dossiers.show', [$tenant, $dossier]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/dossiers/show')
            ->has('dossier.document_requests', 1));

    expect(DocumentRequest::query()->where('dossier_id', $dossier->id)->first())
        ->not->toBeNull()
        ->and(Activity::query()
            ->where('event', AuditEvent::DocumentRequestCreated->value)
            ->exists())->toBeTrue();
});

test('staff cannot attach a dossier to a client in another tenant', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();
    $tenantA = app(ProvisionTenant::class)('Tenant A', $ownerA);
    $tenantB = app(ProvisionTenant::class)('Tenant B', $ownerB);

    $tenantB->makeCurrent();
    $foreignClient = Client::factory()->create(['tenant_id' => $tenantB->id]);

    $this->actingAs($ownerA)
        ->post(route('workspaces.dossiers.store', $tenantA), [
            'client_id' => $foreignClient->id,
            'title' => 'Invalid dossier',
        ])
        ->assertSessionHasErrors('client_id');
});

test('read only staff cannot create clients or dossiers', function () {
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);
    $reader->assignRole(Role::ReadOnly->value);

    $this->actingAs($reader)
        ->post(route('workspaces.clients.store', $tenant), [
            'name' => 'Jane Client',
            'email' => 'jane@example.com',
        ])
        ->assertForbidden();
});
