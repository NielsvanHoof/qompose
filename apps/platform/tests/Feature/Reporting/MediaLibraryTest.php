<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenant;
use App\Enums\DocumentRequestStatus;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\TenantMembership;
use App\Models\UploadedDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('staff can view the media library with pending and uploaded documents', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => 'Payroll 2025',
    ]);

    $pending = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'title' => 'Pending payslip',
        'status' => DocumentRequestStatus::Pending,
    ]);

    $uploadedRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'title' => 'Uploaded ID',
        'status' => DocumentRequestStatus::Submitted,
    ]);

    UploadedDocument::factory()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $uploadedRequest->id,
        'original_filename' => 'passport.pdf',
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.media.index', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/media/index')
            ->where('can_download', true)
            ->has('documents.data', 2)
            ->where('documents.total', 2)
            ->has('indexQuery')
            ->where('documents.data.0.title', fn (string $title) => in_array($title, [
                $pending->title,
                $uploadedRequest->title,
            ], true))
            ->where('documents.data', fn ($documents) => collect($documents)->contains(
                fn (array $document): bool => $document['title'] === 'Pending payslip'
                    && $document['status'] === 'pending'
                    && $document['uploaded_document'] === null
                    && $document['client_name'] === $client->name
                    && $document['dossier']['id'] === $dossier->id,
            ))
            ->where('documents.data', fn ($documents) => collect($documents)->contains(
                fn (array $document): bool => $document['title'] === 'Uploaded ID'
                    && $document['status'] === 'submitted'
                    && $document['uploaded_document']['original_filename'] === 'passport.pdf',
            )));
});

test('media library does not include document requests from another tenant', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();
    $tenantA = app(ProvisionTenant::class)->handle('Tenant A', $ownerA);
    $tenantB = app(ProvisionTenant::class)->handle('Tenant B', $ownerB);

    $tenantB->makeCurrent();
    $clientB = Client::factory()->create(['tenant_id' => $tenantB->id]);
    $dossierB = Dossier::factory()->create([
        'tenant_id' => $tenantB->id,
        'client_id' => $clientB->id,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $tenantB->id,
        'dossier_id' => $dossierB->id,
        'title' => 'Foreign request',
    ]);

    $tenantA->makeCurrent();
    $clientA = Client::factory()->create(['tenant_id' => $tenantA->id]);
    $dossierA = Dossier::factory()->create([
        'tenant_id' => $tenantA->id,
        'client_id' => $clientA->id,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $tenantA->id,
        'dossier_id' => $dossierA->id,
        'title' => 'Own request',
    ]);

    $this->actingAs($ownerA)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->get(workspaceRoute('workspaces.media.index', $tenantA))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/media/index')
            ->has('documents.data', 1)
            ->where('documents.total', 1)
            ->where('documents.data.0.title', 'Own request'));
});

test('reviewer can browse the media library but cannot download', function () {
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
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'status' => DocumentRequestStatus::Submitted,
    ]);
    $uploaded = UploadedDocument::factory()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
    ]);

    $this->actingAs($reviewer)
        ->withSession([
            'active_tenant_id' => $tenant->id,
            'auth.password_confirmed_at' => now()->getTimestamp(),
        ])
        ->get(workspaceRoute('workspaces.media.index', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/media/index')
            ->where('can_download', false)
            ->has('documents.data', 1)
            ->where('documents.total', 1));

    $this->get(workspaceRoute(
        'workspaces.uploaded-documents.download',
        $tenant,
        ['uploadedDocument' => $uploaded],
    ))
        ->assertForbidden();
});

test('guests cannot view the media library', function () {
    $this->get(workspaceRoute('workspaces.media.index', 'acme-accountants'))
        ->assertRedirect(route('login'));
});
