<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenant;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('tenant member can view dossiers in their workspace', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);

    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => 'Annual accounts 2025',
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dossiers/show')
            ->where('dossier.title', 'Annual accounts 2025'));
});

test('users cannot access workspaces they do not belong to', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($outsider)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]))
        ->assertNotFound();
});

test('users cannot read dossiers from another tenant via id guessing', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();

    $tenantA = app(ProvisionTenant::class)->handle('Tenant A', $ownerA);
    $tenantB = app(ProvisionTenant::class)->handle('Tenant B', $ownerB);

    $tenantB->makeCurrent();
    setPermissionsTeamId($tenantB->id);
    $clientB = Client::factory()->create(['tenant_id' => $tenantB->id]);
    $foreignDossier = Dossier::factory()->create([
        'tenant_id' => $tenantB->id,
        'client_id' => $clientB->id,
        'title' => 'Foreign dossier',
    ]);

    $this->actingAs($ownerA)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->get(workspaceRoute('workspaces.dossiers.show', $tenantA, ['dossier' => $foreignDossier]))
        ->assertNotFound();
});

test('the workspace in the url takes precedence over the session tenant', function () {
    $user = User::factory()->create();

    $tenantA = app(ProvisionTenant::class)->handle('Tenant A', $user, ownerRole: Role::Owner);
    $tenantB = app(ProvisionTenant::class)->handle('Tenant B', User::factory()->create(), ownerRole: Role::Owner);

    TenantMembership::query()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    setPermissionsTeamId($tenantB->id);
    $user->unsetRelation('roles');
    $user->assignRole(Role::ReadOnly->value);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->get(workspaceRoute('workspaces.dossiers.index', $tenantB))
        ->assertOk()
        ->assertSessionHas('active_tenant_id', $tenantB->id)
        ->assertInertia(fn (Assert $page) => $page
            ->where('current_firm.slug', $tenantB->slug));
});

test('members can switch their active firm', function () {
    $user = User::factory()->create();

    $tenantA = app(ProvisionTenant::class)->handle('Tenant A', $user, ownerRole: Role::Owner);
    $tenantB = app(ProvisionTenant::class)->handle('Tenant B', User::factory()->create(), ownerRole: Role::Owner);

    TenantMembership::query()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    setPermissionsTeamId($tenantB->id);
    $user->unsetRelation('roles');
    $user->assignRole(Role::ReadOnly->value);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->post(route('firms.activate', $tenantB))
        ->assertRedirect(workspaceRoute('workspaces.dashboard', $tenantB));

    $this->get(workspaceRoute('workspaces.dashboard', $tenantB))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/dashboard'));
});

test('users cannot activate a firm they do not belong to', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Tenant A', $owner);
    $foreignTenant = app(ProvisionTenant::class)->handle('Tenant B', User::factory()->create());

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('firms.activate', $foreignTenant))
        ->assertForbidden();
});

test('read only members cannot create dossiers', function () {
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $reader->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    setPermissionsTeamId($tenant->id);
    $reader->unsetRelation('roles');
    $reader->assignRole(Role::ReadOnly->value);

    expect($reader->can(Permission::CreateDossiers->value))->toBeFalse();
});

test('tenant routes are registered with the expected middleware', function () {
    $route = Route::getRoutes()->getByName('workspaces.dossiers.index');

    expect($route)->not->toBeNull();
    expect($route->middleware())->toContain('auth');
    expect($route->middleware())->toContain(App\Http\Middleware\InitializeTenantFromRoute::class);
    expect($route->middleware())->toContain(App\Http\Middleware\EnsureValidTenantMembership::class);
    expect($route->middleware())->toContain(App\Http\Middleware\SetPermissionTeamContext::class);
    expect($route->middleware())->not->toContain(Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class);
    expect($route->uri())->toBe('workspaces/{tenant}/dossiers');
});

test('workspace pages load tenant memberships only once', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dossiers.index', $tenant))
        ->assertOk();

    // Count only the relation eager-load, not the route initializer's whereHas exists subquery.
    $membershipEagerLoads = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'from "tenant_memberships"')
            && str_contains($query['query'], '"user_id" in'));

    expect($membershipEagerLoads)->toHaveCount(1);
});
