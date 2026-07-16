<?php

declare(strict_types=1);

use App\Actions\Dossiers\DeleteDocumentRequest;
use App\Actions\Dossiers\UploadDocumentForRequest;
use App\Enums\DocumentRequestStatus;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    config()->set('filesystems.default', 'local');
});

test('replacing an upload commits new metadata before deleting the old file', function () {
    $documentRequest = createDocumentRequestForUploadLifecycleTest();
    $upload = app(UploadDocumentForRequest::class);

    $original = $upload->handle(
        $documentRequest,
        UploadedFile::fake()->create('original.pdf', 100, 'application/pdf'),
    );
    $replacement = $upload->handle(
        $documentRequest,
        UploadedFile::fake()->create('replacement.pdf', 120, 'application/pdf'),
    );

    expect($replacement->id)->toBe($original->id)
        ->and($replacement->original_filename)->toBe('replacement.pdf')
        ->and(UploadedDocument::query()->count())->toBe(1);

    Storage::disk('local')
        ->assertMissing($original->path)
        ->assertExists($replacement->path);
});

test('a failed replacement keeps the previous database record and file', function () {
    $documentRequest = createDocumentRequestForUploadLifecycleTest();
    $upload = app(UploadDocumentForRequest::class);

    $original = $upload->handle(
        $documentRequest,
        UploadedFile::fake()->create('original.pdf', 100, 'application/pdf'),
    );

    $event = 'eloquent.updating: '.UploadedDocument::class;
    Event::listen($event, static function (): never {
        throw new RuntimeException('Simulated database failure.');
    });

    try {
        expect(fn () => $upload->handle(
            $documentRequest,
            UploadedFile::fake()->create('replacement.pdf', 120, 'application/pdf'),
        ))->toThrow(RuntimeException::class, 'Simulated database failure.');
    } finally {
        Event::forget($event);
    }

    $persistedUpload = UploadedDocument::query()->sole();

    expect($persistedUpload->path)->toBe($original->path)
        ->and($persistedUpload->original_filename)->toBe('original.pdf')
        ->and(Storage::disk('local')->allFiles())->toBe([$original->path]);
});

test('a failed initial upload removes the newly stored file', function () {
    $documentRequest = createDocumentRequestForUploadLifecycleTest();
    $upload = app(UploadDocumentForRequest::class);

    $event = 'eloquent.creating: '.UploadedDocument::class;
    Event::listen($event, static function (): never {
        throw new RuntimeException('Simulated database failure.');
    });

    try {
        expect(fn () => $upload->handle(
            $documentRequest,
            UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
        ))->toThrow(RuntimeException::class, 'Simulated database failure.');
    } finally {
        Event::forget($event);
    }

    expect(UploadedDocument::query()->exists())->toBeFalse()
        ->and($documentRequest->fresh()->status)->toBe(DocumentRequestStatus::Pending)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('deleting a document request removes its database graph and stored file', function () {
    $documentRequest = createDocumentRequestForUploadLifecycleTest();
    $uploadedDocument = app(UploadDocumentForRequest::class)->handle(
        $documentRequest,
        UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    );

    app(DeleteDocumentRequest::class)->handle($documentRequest);

    expect(DocumentRequest::query()->whereKey($documentRequest->id)->exists())->toBeFalse()
        ->and(UploadedDocument::query()->whereKey($uploadedDocument->id)->exists())->toBeFalse();

    Storage::disk('local')->assertMissing($uploadedDocument->path);
});

test('a failed document request deletion leaves its database graph and file intact', function () {
    $documentRequest = createDocumentRequestForUploadLifecycleTest();
    $uploadedDocument = app(UploadDocumentForRequest::class)->handle(
        $documentRequest,
        UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    );

    $event = 'eloquent.deleting: '.DocumentRequest::class;
    Event::listen($event, static function (): never {
        throw new RuntimeException('Simulated database failure.');
    });

    try {
        expect(fn () => app(DeleteDocumentRequest::class)->handle($documentRequest))
            ->toThrow(RuntimeException::class, 'Simulated database failure.');
    } finally {
        Event::forget($event);
    }

    expect(DocumentRequest::query()->whereKey($documentRequest->id)->exists())->toBeTrue()
        ->and(UploadedDocument::query()->whereKey($uploadedDocument->id)->exists())->toBeTrue();

    Storage::disk('local')->assertExists($uploadedDocument->path);
});

function createDocumentRequestForUploadLifecycleTest(): DocumentRequest
{
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    return DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'status' => DocumentRequestStatus::Pending,
    ]);
}
