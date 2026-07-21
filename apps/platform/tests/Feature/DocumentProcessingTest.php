<?php

declare(strict_types=1);

use App\Actions\Dossiers\UploadDocumentForRequestAction;
use App\Actions\Tenancy\ProvisionTenantAction;
use App\Enums\DocumentProcessingStatus;
use App\Enums\DocumentRequestStatus;
use App\Jobs\ProcessUploadedDocument;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    config()->set('filesystems.default', 'local');
});

test('uploading a document dispatches the mock OCR processing job', function () {
    Queue::fake();

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
        'status' => DocumentRequestStatus::Pending,
    ]);

    $uploaded = app(UploadDocumentForRequestAction::class)->handle(
        $documentRequest,
        UploadedFile::fake()->create('payslip.pdf', 100, 'application/pdf'),
    );

    expect($uploaded->processing_status)->toBe(DocumentProcessingStatus::Pending);

    Queue::assertPushed(ProcessUploadedDocument::class, function (ProcessUploadedDocument $job) use ($uploaded): bool {
        return $job->uploadedDocumentId === $uploaded->id;
    });
});

test('staff dossier show includes processing fields for uploaded documents', function () {
    Queue::fake();

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
        'status' => DocumentRequestStatus::Pending,
    ]);

    app(UploadDocumentForRequestAction::class)->handle(
        $documentRequest,
        UploadedFile::fake()->create('payslip.pdf', 100, 'application/pdf'),
    );

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dossiers.show', $tenant, [
            'dossier' => $dossier,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dossiers/show')
            ->has('dossier.document_requests.0.uploaded_document', fn (Assert $document) => $document
                ->where('original_filename', 'payslip.pdf')
                ->where('processing_status', DocumentProcessingStatus::Pending->value)
                ->missing('extracted_text')
                ->etc()
            )
        );
});
