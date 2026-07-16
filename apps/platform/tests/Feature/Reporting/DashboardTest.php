<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Actions\Tenancy\ProvisionTenant;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('users without firm memberships are sent to firm setup', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('onboarding.firm.create'));
});

test('users without firm memberships can view firm setup', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('onboarding.firm.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('onboarding/firm'));
});

test('users with one firm see its dashboard', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    app(ProvisionTenant::class)('Acme Accountants', $user);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/dashboard'));
});

test('firm dashboard shows tenant-scoped operational metrics', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);
    $tenant->makeCurrent();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $awaitingClient = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::AwaitingClient,
    ]);
    $inReview = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::InReview,
    ]);
    Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::Completed,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $awaitingClient->id,
        'status' => DocumentRequestStatus::Pending,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $inReview->id,
        'status' => DocumentRequestStatus::Rejected,
    ]);

    $foreignTenant = Tenant::factory()->create();
    $foreignTenant->makeCurrent();
    $foreignClient = Client::factory()->create(['tenant_id' => $foreignTenant->id]);
    $foreignDossier = Dossier::factory()->create([
        'tenant_id' => $foreignTenant->id,
        'client_id' => $foreignClient->id,
        'status' => DossierStatus::AwaitingClient,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $foreignTenant->id,
        'dossier_id' => $foreignDossier->id,
        'status' => DocumentRequestStatus::Pending,
    ]);

    $tenant->makeCurrent();

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/dashboard')
            ->where('metrics.clients', 1)
            ->where('metrics.open_dossiers', 2)
            ->where('metrics.awaiting_client', 1)
            ->where('metrics.in_review', 1)
            ->where('metrics.outstanding_document_requests', 2));
});

test('users with multiple firms can choose which firm to open', function () {
    $user = User::factory()->create();
    $firstTenant = Tenant::factory()->create(['name' => 'Acme Accountants', 'slug' => 'acme-accountants']);
    $secondTenant = Tenant::factory()->create(['name' => 'Beta Tax', 'slug' => 'beta-tax']);

    TenantMembership::factory()->create(['tenant_id' => $firstTenant->id, 'user_id' => $user->id]);
    TenantMembership::factory()->create(['tenant_id' => $secondTenant->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('firms', [
                ['name' => 'Acme Accountants', 'slug' => 'acme-accountants'],
                ['name' => 'Beta Tax', 'slug' => 'beta-tax'],
            ]));
});

test('verified users can set up their firm', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.firm.store'), ['name' => 'Acme Accountants'])
        ->assertRedirect(route('workspaces.clients.create'));

    $tenant = Tenant::query()->sole();
    $membership = TenantMembership::query()->sole();

    setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles');

    expect($tenant->slug)->toBe('acme-accountants')
        ->and($membership->tenant_id)->toBe($tenant->id)
        ->and($membership->user_id)->toBe($user->id)
        ->and($membership->isActive())->toBeTrue()
        ->and($user->hasRole(Role::Owner->value))->toBeTrue();
});

test('firm setup requires a name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('onboarding.firm.create'))
        ->post(route('onboarding.firm.store'), ['name' => ''])
        ->assertRedirect(route('onboarding.firm.create'))
        ->assertSessionHasErrors('name');
});

test('unverified users are redirected to email verification', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('verification.notice'));

    $this->get(route('onboarding.firm.create'))
        ->assertRedirect(route('verification.notice'));
});

test('guests cannot set up a firm', function () {
    $this->post(route('onboarding.firm.store'), ['name' => 'Acme Accountants'])
        ->assertRedirect(route('login'));
});
