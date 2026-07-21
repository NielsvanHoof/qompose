<?php

declare(strict_types=1);

use App\Enums\DossierStatus;
use App\Enums\Role;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Policies\Dossiers\DocumentRequestPolicy;

/**
 * @return array{policy: DocumentRequestPolicy, owner: mixed, tenant: mixed, documentRequest: DocumentRequest, dossier: Dossier}
 */
function documentRequestPolicyContext(): array
{
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::InReview,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
    ]);

    return [
        'policy' => new DocumentRequestPolicy,
        'owner' => $owner,
        'tenant' => $tenant,
        'documentRequest' => $documentRequest,
        'dossier' => $dossier,
    ];
}

test('owner can view, upload, update, and review document requests on open dossiers', function () {
    $context = documentRequestPolicyContext();
    $policy = $context['policy'];
    $owner = $context['owner'];
    $documentRequest = $context['documentRequest'];

    expect($policy->viewAny($owner))->toBeTrue()
        ->and($policy->view($owner, $documentRequest))->toBeTrue()
        ->and($policy->upload($owner, $documentRequest))->toBeTrue()
        ->and($policy->update($owner, $documentRequest))->toBeTrue()
        ->and($policy->review($owner, $documentRequest))->toBeTrue();
});

test('review and upload are denied when the dossier is completed', function () {
    $context = documentRequestPolicyContext();
    $policy = $context['policy'];
    $owner = $context['owner'];
    $documentRequest = $context['documentRequest'];
    $dossier = $context['dossier'];

    $dossier->forceFill(['status' => DossierStatus::Completed])->save();
    $documentRequest->setRelation('dossier', $dossier->fresh());

    expect($policy->upload($owner, $documentRequest))->toBeFalse()
        ->and($policy->review($owner, $documentRequest))->toBeFalse();
});

test('reviewers can review but cannot upload or update structure', function () {
    $context = documentRequestPolicyContext();
    $policy = $context['policy'];
    $tenant = $context['tenant'];
    $documentRequest = $context['documentRequest'];

    $reviewer = workspaceMember($tenant, Role::Reviewer);

    expect($policy->view($reviewer, $documentRequest))->toBeTrue()
        ->and($policy->review($reviewer, $documentRequest))->toBeTrue()
        ->and($policy->upload($reviewer, $documentRequest))->toBeFalse()
        ->and($policy->create($reviewer))->toBeFalse();
});
