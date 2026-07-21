<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenant;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('clients index paginates results and exposes indexQuery config', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();

    // Create more than one page (default per_page = 15).
    Client::factory()->count(16)->create(['tenant_id' => $tenant->id]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.clients.index', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/index')
            ->has('clients.data', 15)
            ->where('clients.total', 16)
            ->where('clients.current_page', 1)
            ->where('clients.last_page', 2)
            ->has('indexQuery.filters')
            ->has('indexQuery.sorts')
            ->where('indexQuery.defaults.sort', 'name')
            ->where('filters', [])
            ->where('sort', null));

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.clients.index', $tenant).'?page=2')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('clients.data', 1)
            ->where('clients.current_page', 2));
});

test('clients index filters by search query across name and email', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();

    Client::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Acme Corp',
        'email' => 'info@acme.test',
    ]);
    Client::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Other Client',
        'email' => 'other@example.com',
    ]);
    Client::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Partner BV',
        'email' => 'hello@acme-partner.test',
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.clients.index', $tenant).'?filter[q]=acme')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('clients.data', 2)
            ->where('clients.total', 2)
            ->where('filters.q', 'acme')
            ->where('clients.data', fn ($clients) => collect($clients)->every(
                fn (array $client): bool => str_contains(mb_strtolower($client['name'].$client['email']), 'acme'),
            )));
});

test('clients index inertia page url keeps readable filter brackets', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();

    Client::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Acme Corp',
        'email' => 'info@acme.test',
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.clients.index', $tenant).'?filter[q]=acme&sort=-name')
        ->assertOk()
        ->assertInertia(function (Assert $page) {
            $url = $page->toArray()['url'];

            // fullUrl() encodes [ ] as %5B/%5D; urlResolver must keep Spatie filters readable.
            expect($url)
                ->toContain('filter[q]=acme')
                ->toContain('sort=-name')
                ->not->toContain('%5B')
                ->not->toContain('%5D');

            $page
                ->where('filters.q', 'acme')
                ->where('sort', '-name');
        });
});

test('clients index sorts by name descending', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();

    Client::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Alpha Client',
        'email' => 'alpha@example.com',
    ]);
    Client::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Zulu Client',
        'email' => 'zulu@example.com',
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.clients.index', $tenant).'?sort=-name')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort', '-name')
            ->where('clients.data.0.name', 'Zulu Client')
            ->where('clients.data.1.name', 'Alpha Client'));
});

test('clients index rejects invalid filters', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.clients.index', $tenant).'?filter[unknown]=x')
        ->assertStatus(400);
});

test('clients index does not leak another tenants clients', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();
    $tenantA = app(ProvisionTenant::class)->handle('Tenant A', $ownerA);
    $tenantB = app(ProvisionTenant::class)->handle('Tenant B', $ownerB);

    $tenantB->makeCurrent();
    Client::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Foreign Client',
        'email' => 'foreign@example.com',
    ]);

    $tenantA->makeCurrent();
    Client::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Own Client',
        'email' => 'own@example.com',
    ]);

    $this->actingAs($ownerA)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->get(workspaceRoute('workspaces.clients.index', $tenantA).'?filter[q]=Client')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.name', 'Own Client'));
});
