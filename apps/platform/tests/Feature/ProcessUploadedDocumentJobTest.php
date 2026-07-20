<?php

declare(strict_types=1);

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Enums\DocumentProcessingStatus;
use App\Jobs\ProcessUploadedDocument;
use App\Models\Activity;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use App\Services\Ocr\OcrOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    config(['ocr.driver' => 'mock']);
});

test('processing job extracts mock text and marks the document completed', function () {
    $uploaded = createUploadedDocumentForProcessingTest();

    (new ProcessUploadedDocument($uploaded->id))->handle(
        app(OcrOrchestrator::class),
        app(LogAuditActivity::class),
    );

    $uploaded->refresh();

    expect($uploaded->processing_status)->toBe(DocumentProcessingStatus::Completed)
        ->and($uploaded->extracted_text)->toContain('key_values')
        ->and($uploaded->extracted_text)->toContain($uploaded->original_filename)
        ->and($uploaded->processing_error)->toBeNull()
        ->and($uploaded->processing_finished_at)->not->toBeNull();

    expect(Activity::query()
        ->where('event', AuditEvent::DocumentProcessingStarted->value)
        ->where('subject_id', $uploaded->id)
        ->exists())->toBeTrue();

    expect(Activity::query()
        ->where('event', AuditEvent::DocumentProcessingCompleted->value)
        ->where('subject_id', $uploaded->id)
        ->exists())->toBeTrue();
});

test('processing job is idempotent when the document is already completed', function () {
    $uploaded = createUploadedDocumentForProcessingTest([
        'processing_status' => DocumentProcessingStatus::Completed,
        'extracted_text' => 'Already extracted',
        'processing_finished_at' => now(),
    ]);

    (new ProcessUploadedDocument($uploaded->id))->handle(
        app(OcrOrchestrator::class),
        app(LogAuditActivity::class),
    );

    $uploaded->refresh();

    expect($uploaded->extracted_text)->toBe('Already extracted')
        ->and($uploaded->processing_status)->toBe(DocumentProcessingStatus::Completed);

    expect(Activity::query()
        ->where('event', AuditEvent::DocumentProcessingStarted->value)
        ->where('subject_id', $uploaded->id)
        ->exists())->toBeFalse();
});

test('processing audit events stay tenant-scoped', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $uploadedA = createUploadedDocumentForProcessingTest(tenant: $tenantA);

    (new ProcessUploadedDocument($uploadedA->id))->handle(
        app(OcrOrchestrator::class),
        app(LogAuditActivity::class),
    );

    expect(Activity::query()
        ->forTenant($tenantB)
        ->where('event', AuditEvent::DocumentProcessingCompleted->value)
        ->where('subject_id', $uploadedA->id)
        ->count())->toBe(0);

    expect(Activity::query()
        ->forTenant($tenantA)
        ->where('event', AuditEvent::DocumentProcessingCompleted->value)
        ->where('subject_id', $uploadedA->id)
        ->count())->toBe(1);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function createUploadedDocumentForProcessingTest(
    array $overrides = [],
    ?Tenant $tenant = null,
): UploadedDocument {
    $tenant ??= Tenant::factory()->create();
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

    $path = 'tenants/'.$tenant->id.'/dossiers/'.$dossier->id.'/file.pdf';
    Storage::disk('local')->put($path, 'fake-pdf-bytes');

    return UploadedDocument::factory()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
        'disk' => 'local',
        'path' => $path,
        'original_filename' => 'statement.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 2048,
        'processing_status' => DocumentProcessingStatus::Pending,
        ...$overrides,
    ]);
}
