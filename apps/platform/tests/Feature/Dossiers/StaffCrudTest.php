<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenant;
use App\Enums\AuditEvent;
use App\Enums\DocumentRequestStatus;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Activity;
use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\TenantMembership;
use App\Models\UploadedDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('staff can create a client, dossier, and document request', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.clients.index', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/index')
            ->has('clients', 0));

    $this->post(workspaceRoute('workspaces.clients.store', $tenant), [
        'name' => 'Jane Client',
        'email' => 'jane@example.com',
    ])->assertRedirect(workspaceRoute('workspaces.dossiers.create', $tenant));

    $tenant->makeCurrent();
    $client = Client::query()->sole();

    $this->post(workspaceRoute('workspaces.dossiers.store', $tenant), [
        'client_id' => $client->id,
        'title' => '2025 Payroll dossier',
        'reference' => 'PAY-2025-001',
    ])->assertRedirect();

    $dossier = Dossier::query()->sole();

    $this->post(workspaceRoute('workspaces.dossiers.document-requests.store', $tenant, [
        'dossier' => $dossier,
    ]), [
        'type' => 'file',
        'title' => 'Payslip January 2025',
        'instructions' => 'Upload the original PDF.',
    ])->assertRedirect(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]));

    $this->get(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dossiers/show')
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
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->post(workspaceRoute('workspaces.dossiers.store', $tenantA), [
            'client_id' => $foreignClient->id,
            'title' => 'Invalid dossier',
        ])
        ->assertSessionHasErrors('client_id');
});

test('read only staff cannot create clients or dossiers', function () {
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    // Membership is required so the 403 comes from policy, not tenant middleware.
    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $reader->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);
    $reader->unsetRelation('roles');
    $reader->assignRole(Role::ReadOnly->value);

    $this->actingAs($reader)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(workspaceRoute('workspaces.clients.store', $tenant), [
            'name' => 'Jane Client',
            'email' => 'jane@example.com',
        ])
        ->assertForbidden();

    $this->post(workspaceRoute('workspaces.dossiers.store', $tenant), [
        'client_id' => 1,
        'title' => 'Should not create',
    ])->assertForbidden();
});

test('staff can issue and revoke a client access grant', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $tenant->makeCurrent();
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(workspaceRoute('workspaces.dossiers.access-grants.store', $tenant, [
            'dossier' => $dossier,
        ]), [
            'expires_in_days' => 7,
            'send_invite' => false,
        ])
        ->assertRedirect(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]))
        ->assertSessionHas('access_grant_token')
        ->assertSessionHas('access_grant_portal_url');

    $grant = ClientAccessGrant::query()->sole();
    expect($grant->isValid())->toBeTrue()
        ->and($grant->created_by)->toBe($owner->id);

    $this->delete(workspaceRoute('workspaces.access-grants.destroy', $tenant, ['grant' => $grant]))
        ->assertRedirect(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]));

    expect($grant->fresh()->isValid())->toBeFalse()
        ->and($grant->fresh()->revoked_at)->not->toBeNull();
});

test('read only staff cannot issue client access grants', function () {
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $reader->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);
    $reader->unsetRelation('roles');
    $reader->assignRole(Role::ReadOnly->value);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($reader)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(workspaceRoute('workspaces.dossiers.access-grants.store', $tenant, [
            'dossier' => $dossier,
        ]))
        ->assertForbidden();
});

test('staff can upload a document for a document request', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $tenant->makeCurrent();
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'title' => 'Payslip January 2025',
    ]);

    $file = UploadedFile::fake()->create('payslip.pdf', 120, 'application/pdf');

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(workspaceRoute('workspaces.dossiers.document-requests.upload', $tenant, [
            'dossier' => $dossier,
            'documentRequest' => $documentRequest,
        ]), [
            'document' => $file,
        ])
        ->assertRedirect(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]));

    $uploaded = UploadedDocument::query()->sole();

    expect($documentRequest->fresh()->status)->toBe(DocumentRequestStatus::Submitted)
        ->and($uploaded->original_filename)->toBe('payslip.pdf')
        ->and($uploaded->document_request_id)->toBe($documentRequest->id);

    Storage::disk($uploaded->disk)->assertExists($uploaded->path);

    $this->get(workspaceRoute('workspaces.uploaded-documents.download', $tenant, [
        'uploadedDocument' => $uploaded,
    ]))
        ->assertOk();

    expect(Activity::query()
        ->where('event', AuditEvent::DocumentUploaded->value)
        ->exists())->toBeTrue();
});

test('read only staff cannot upload documents', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $reader->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);
    $reader->unsetRelation('roles');
    $reader->assignRole(Role::ReadOnly->value);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
    ]);

    $this->actingAs($reader)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(workspaceRoute('workspaces.dossiers.document-requests.upload', $tenant, [
            'dossier' => $dossier,
            'documentRequest' => $documentRequest,
        ]), [
            'document' => UploadedFile::fake()->create('payslip.pdf', 120, 'application/pdf'),
        ])
        ->assertForbidden();
});
