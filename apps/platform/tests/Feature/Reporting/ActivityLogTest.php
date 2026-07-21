<?php

declare(strict_types=1);

use App\Actions\Audit\LogAuditActivity;
use App\Actions\Portal\CreateClientAccessGrant;
use App\Actions\Tenancy\ProvisionTenant;
use App\Enums\AuditEvent;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('owner can view the activity log with structured audit entries', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => 'Payroll 2025',
    ]);

    // Clear dossier-created noise so the manual view event is easy to assert.
    Activity::query()->delete();

    app(LogAuditActivity::class)->handle(
        AuditEvent::DossierViewed,
        $dossier,
        causer: $owner,
        includeRequestContext: false,
    );

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.activity.index', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/activity/index')
            ->has('activities', 1)
            ->where('activities.0.event', AuditEvent::DossierViewed->value)
            ->where('activities.0.label', AuditEvent::DossierViewed->label())
            ->where('activities.0.causer_name', $owner->name)
            ->where('activities.0.subject.type', 'Dossier')
            ->where('activities.0.subject.id', $dossier->id)
            ->where('activities.0.subject.name', 'Payroll 2025'));
});

test('activity log does not include entries from another tenant', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();
    $tenantA = app(ProvisionTenant::class)->handle('Tenant A', $ownerA);
    $tenantB = app(ProvisionTenant::class)->handle('Tenant B', $ownerB);

    $tenantB->makeCurrent();
    setPermissionsTeamId($tenantB->id);
    $clientB = Client::factory()->create(['tenant_id' => $tenantB->id]);
    $dossierB = Dossier::factory()->create([
        'tenant_id' => $tenantB->id,
        'client_id' => $clientB->id,
        'title' => 'Foreign dossier',
    ]);
    Activity::query()->delete();
    app(LogAuditActivity::class)->handle(
        AuditEvent::DossierViewed,
        $dossierB,
        causer: $ownerB,
        includeRequestContext: false,
    );

    $tenantA->makeCurrent();
    setPermissionsTeamId($tenantA->id);
    $clientA = Client::factory()->create(['tenant_id' => $tenantA->id]);
    $dossierA = Dossier::factory()->create([
        'tenant_id' => $tenantA->id,
        'client_id' => $clientA->id,
        'title' => 'Own dossier',
    ]);
    Activity::query()->delete();
    app(LogAuditActivity::class)->handle(
        AuditEvent::DossierCompleted,
        $dossierA,
        causer: $ownerA,
        includeRequestContext: false,
    );

    $this->actingAs($ownerA)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->get(workspaceRoute('workspaces.activity.index', $tenantA))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/activity/index')
            ->has('activities', 1)
            ->where('activities.0.event', AuditEvent::DossierCompleted->value)
            ->where('activities.0.subject.name', 'Own dossier'));
});

test('activity log resolves client access grant subjects using the dossier title', function () {
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

    $result = app(CreateClientAccessGrant::class)->handle($dossier, $owner, 7);

    Activity::query()->delete();

    app(LogAuditActivity::class)->handle(
        AuditEvent::ClientPortalAccessGrantCreated,
        $result['grant'],
        causer: $owner,
        includeRequestContext: false,
    );

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.activity.index', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/activity/index')
            ->has('activities', 1)
            ->where('activities.0.event', AuditEvent::ClientPortalAccessGrantCreated->value)
            ->where('activities.0.subject.type', 'ClientAccessGrant')
            ->where('activities.0.subject.id', $result['grant']->id)
            ->where('activities.0.subject.name', 'Annual accounts 2025'));
});

test('reviewer cannot view the activity log', function () {
    $owner = User::factory()->create();
    $reviewer = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $reviewer->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);
    $reviewer->unsetRelation('roles');
    $reviewer->assignRole(Role::Reviewer->value);

    $this->actingAs($reviewer)
        ->withSession([
            'active_tenant_id' => $tenant->id,
            'auth.password_confirmed_at' => now()->getTimestamp(),
        ])
        ->get(workspaceRoute('workspaces.activity.index', $tenant))
        ->assertForbidden();
});

test('guests cannot view the activity log', function () {
    $this->get(workspaceRoute('workspaces.activity.index', 'acme-accountants'))
        ->assertRedirect(route('login'));
});
