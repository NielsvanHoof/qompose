<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\UploadedDocument;
use App\Policies\Dossiers\UploadedDocumentPolicy;

test('owner can view and download uploaded documents', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
    ]);
    $uploadedDocument = UploadedDocument::factory()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
    ]);

    $policy = new UploadedDocumentPolicy;

    expect($policy->view($owner, $uploadedDocument))->toBeTrue()
        ->and($policy->download($owner, $uploadedDocument))->toBeTrue();
});

test('reviewers can view uploads but cannot download without the download permission', function () {
    ['tenant' => $tenant] = provisionWorkspace();
    $reviewer = workspaceMember($tenant, Role::Reviewer);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
    ]);
    $uploadedDocument = UploadedDocument::factory()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
    ]);

    $policy = new UploadedDocumentPolicy;

    expect($policy->view($reviewer, $uploadedDocument))->toBeTrue()
        ->and($policy->download($reviewer, $uploadedDocument))->toBeFalse();
});
