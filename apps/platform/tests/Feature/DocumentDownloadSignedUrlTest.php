<?php

declare(strict_types=1);

use App\Actions\Dossiers\ResolveDocumentTemporaryUrl;
use App\Actions\Tenancy\ProvisionTenant;
use App\Enums\AuditEvent;
use App\Models\Activity;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\UploadedDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('local disk downloads stream through the application', function () {
    Storage::fake('local');

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

    $path = 'tenants/'.$tenant->id.'/dossiers/'.$dossier->id.'/payslip.pdf';
    Storage::disk('local')->put($path, 'local-file-bytes');

    $uploaded = UploadedDocument::factory()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
        'disk' => 'local',
        'path' => $path,
        'original_filename' => 'payslip.pdf',
    ]);

    $this->actingAs($owner)
        ->withSession([
            'active_tenant_id' => $tenant->id,
            'auth.password_confirmed_at' => now()->getTimestamp(),
        ])
        ->get(workspaceRoute('workspaces.uploaded-documents.download', $tenant, [
            'uploadedDocument' => $uploaded,
        ]))
        ->assertOk();

    $this->travel(16)->minutes();

    $this->get(workspaceRoute('workspaces.uploaded-documents.download', $tenant, [
        'uploadedDocument' => $uploaded,
    ]))->assertRedirect(route('password.confirm'));

    expect(Activity::query()
        ->where('event', AuditEvent::DocumentDownloaded->value)
        ->where('subject_id', $uploaded->id)
        ->exists())->toBeTrue();
});

test('resolve document temporary url rewrites the internal minio host for browsers', function () {
    config()->set('filesystems.disks.s3.driver', 's3');
    config()->set('filesystems.disks.s3.endpoint', 'http://minio:9000');
    config()->set('filesystems.disks.s3.url', 'http://localhost:9000');

    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('temporaryUrl')
        ->once()
        ->andReturn('http://minio:9000/local/tenants/1/dossiers/1/file.pdf?X-Amz-Signature=abc');

    $filesystems = Mockery::mock(FilesystemManager::class);
    $filesystems->shouldReceive('disk')
        ->once()
        ->with('s3')
        ->andReturn($disk);

    $uploaded = new UploadedDocument([
        'disk' => 's3',
        'path' => 'tenants/1/dossiers/1/file.pdf',
        'original_filename' => 'file.pdf',
    ]);

    $url = (new ResolveDocumentTemporaryUrl($filesystems))->handle(
        $uploaded,
        now()->addMinutes(5),
    );

    expect($url)->toBe('http://localhost:9000/local/tenants/1/dossiers/1/file.pdf?X-Amz-Signature=abc');
});

test('s3 disk reports temporary url support while local does not', function () {
    config()->set('filesystems.disks.s3.driver', 's3');
    config()->set('filesystems.disks.local.driver', 'local');

    $resolver = app(ResolveDocumentTemporaryUrl::class);

    expect($resolver->supportsTemporaryUrl(new UploadedDocument(['disk' => 's3'])))->toBeTrue()
        ->and($resolver->supportsTemporaryUrl(new UploadedDocument(['disk' => 'local'])))->toBeFalse();
});
