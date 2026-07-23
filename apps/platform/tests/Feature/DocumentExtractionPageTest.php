<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenantAction;
use App\Enums\DocumentProcessingStatus;
use App\Enums\DocumentRequestStatus;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\UploadedDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('staff can open the OCR extraction page for an uploaded document', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);
    $tenant->makeCurrent();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'title' => 'Payslip',
        'status' => DocumentRequestStatus::Submitted,
    ]);
    $uploaded = UploadedDocument::factory()->processed(
        json_encode([
            'document_type' => 'payslip',
            'summary' => 'January payslip',
            'fields' => [
                ['label' => 'BSN', 'value' => '123456789'],
            ],
            'tables' => [
                [
                    'title' => 'Earnings',
                    'headers' => ['Code', 'Amount'],
                    'rows' => [['1000', '2500']],
                ],
            ],
            'notes' => [],
        ], JSON_THROW_ON_ERROR),
    )->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
        'original_filename' => 'payslip.pdf',
        'processing_status' => DocumentProcessingStatus::Completed,
    ]);

    $this->actingAs($owner)
        ->withSession([
            'active_tenant_id' => $tenant->id,
            'auth.password_confirmed_at' => now()->getTimestamp(),
        ])
        ->get(workspaceRoute('workspaces.uploaded-documents.show', $tenant, [
            'uploadedDocument' => $uploaded,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('uploaded-documents/show')
            ->where('uploaded_document.original_filename', 'payslip.pdf')
            ->where('uploaded_document.processing_status', DocumentProcessingStatus::Completed->value)
            ->where('uploaded_document.extraction.document_type', 'payslip')
            ->where('uploaded_document.extraction.fields.0.label', 'BSN')
            ->where('uploaded_document.extraction.fields.0.value', '123456789')
            ->where('dossier.id', $dossier->id)
            ->where('document_request.title', 'Payslip')
        );
});

test('staff from another tenant cannot open an extraction page', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();
    $tenantA = app(ProvisionTenantAction::class)->handle('Firm A', $ownerA);
    $tenantB = app(ProvisionTenantAction::class)->handle('Firm B', $ownerB);

    $tenantA->makeCurrent();
    $client = Client::factory()->create(['tenant_id' => $tenantA->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenantA->id,
        'client_id' => $client->id,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenantA->id,
        'dossier_id' => $dossier->id,
    ]);
    $uploaded = UploadedDocument::factory()->processed()->create([
        'tenant_id' => $tenantA->id,
        'document_request_id' => $documentRequest->id,
    ]);

    $this->actingAs($ownerB)
        ->withSession([
            'active_tenant_id' => $tenantB->id,
            'auth.password_confirmed_at' => now()->getTimestamp(),
        ])
        ->get(workspaceRoute('workspaces.uploaded-documents.show', $tenantA, [
            'uploadedDocument' => $uploaded,
        ]))
        ->assertNotFound();
});

test('staff must recently confirm their password before opening an extraction page', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);
    $tenant->makeCurrent();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
    ]);
    $uploaded = UploadedDocument::factory()->processed()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.uploaded-documents.show', $tenant, [
            'uploadedDocument' => $uploaded,
        ]))
        ->assertRedirect(route('password.confirm'));
});

test('guest cannot open the OCR extraction page', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);
    $tenant->makeCurrent();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
    ]);
    $uploaded = UploadedDocument::factory()->processed()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
    ]);

    $this->get(workspaceRoute('workspaces.uploaded-documents.show', $tenant, [
        'uploadedDocument' => $uploaded,
    ]))->assertRedirect();
});
