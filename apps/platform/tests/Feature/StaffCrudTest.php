<?php

declare(strict_types=1);

use App\Actions\Tenants\ProvisionTenant;
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
        ->get(route('workspaces.clients.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/clients/index')
            ->has('clients', 0));

    $this->post(route('workspaces.clients.store'), [
        'name' => 'Jane Client',
        'email' => 'jane@example.com',
    ])->assertRedirect(route('workspaces.dossiers.create'));

    $tenant->makeCurrent();
    $client = Client::query()->sole();

    $this->post(route('workspaces.dossiers.store'), [
        'client_id' => $client->id,
        'title' => '2025 Payroll dossier',
        'reference' => 'PAY-2025-001',
    ])->assertRedirect();

    $dossier = Dossier::query()->sole();

    $this->post(route('workspaces.dossiers.document-requests.store', $dossier), [
        'title' => 'Payslip January 2025',
        'instructions' => 'Upload the original PDF.',
    ])->assertRedirect(route('workspaces.dossiers.show', $dossier));

    $this->get(route('workspaces.dossiers.show', $dossier))
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
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->post(route('workspaces.dossiers.store'), [
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
        ->post(route('workspaces.clients.store'), [
            'name' => 'Jane Client',
            'email' => 'jane@example.com',
        ])
        ->assertForbidden();

    $this->post(route('workspaces.dossiers.store'), [
        'client_id' => 1,
        'title' => 'Should not create',
    ])->assertForbidden();
});

test('staff can issue and revoke a client access grant', function () {
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
        ->post(route('workspaces.dossiers.access-grants.store', $dossier), [
            'expires_in_days' => 7,
        ])
        ->assertRedirect(route('workspaces.dossiers.show', $dossier))
        ->assertSessionHas('access_grant_token');

    $grant = ClientAccessGrant::query()->sole();
    expect($grant->isValid())->toBeTrue()
        ->and($grant->created_by)->toBe($owner->id);

    $this->delete(route('workspaces.access-grants.destroy', $grant))
        ->assertRedirect(route('workspaces.dossiers.show', $dossier));

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
        ->post(route('workspaces.dossiers.access-grants.store', $dossier))
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
        ->post(route('workspaces.dossiers.document-requests.upload', [
            'dossier' => $dossier,
            'documentRequest' => $documentRequest,
        ]), [
            'document' => $file,
        ])
        ->assertRedirect(route('workspaces.dossiers.show', $dossier));

    $uploaded = UploadedDocument::query()->sole();

    expect($documentRequest->fresh()->status)->toBe(DocumentRequestStatus::Uploaded)
        ->and($uploaded->original_filename)->toBe('payslip.pdf')
        ->and($uploaded->document_request_id)->toBe($documentRequest->id);

    Storage::disk($uploaded->disk)->assertExists($uploaded->path);

    $this->get(route('workspaces.uploaded-documents.download', $uploaded))
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
        ->post(route('workspaces.dossiers.document-requests.upload', [
            'dossier' => $dossier,
            'documentRequest' => $documentRequest,
        ]), [
            'document' => UploadedFile::fake()->create('payslip.pdf', 120, 'application/pdf'),
        ])
        ->assertForbidden();
});
