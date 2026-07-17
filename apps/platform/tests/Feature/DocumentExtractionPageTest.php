<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenant;
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
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);
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
            'key_values' => ['BSN' => '123456789'],
            'tables' => [[['Code', 'Amount'], ['1000', '2500']]],
        ], JSON_THROW_ON_ERROR),
    )->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
        'original_filename' => 'payslip.pdf',
        'processing_status' => DocumentProcessingStatus::Completed,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.uploaded-documents.show', $tenant, [
            'uploadedDocument' => $uploaded,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('uploaded-documents/show')
            ->where('uploaded_document.original_filename', 'payslip.pdf')
            ->where('uploaded_document.processing_status', DocumentProcessingStatus::Completed->value)
            ->where('uploaded_document.extraction.key_values.BSN', '123456789')
            ->where('dossier.id', $dossier->id)
            ->where('document_request.title', 'Payslip')
        );
});

test('staff from another tenant cannot open an extraction page', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();
    $tenantA = app(ProvisionTenant::class)->handle('Firm A', $ownerA);
    $tenantB = app(ProvisionTenant::class)->handle('Firm B', $ownerB);

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
        ->withSession(['active_tenant_id' => $tenantB->id])
        ->get(workspaceRoute('workspaces.uploaded-documents.show', $tenantA, [
            'uploadedDocument' => $uploaded,
        ]))
        ->assertNotFound();
});

test('guest cannot open the OCR extraction page', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);
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
