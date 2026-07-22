<?php

declare(strict_types=1);

use App\Enums\DossierStatus;
use App\Enums\Role;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use App\Policies\Dossiers\DossierPolicy;

/**
 * @return array{policy: DossierPolicy, owner: User, tenant: mixed, dossier: Dossier}
 */
function dossierPolicyContext(): array
{
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::Draft,
    ]);

    return [
        'policy' => new DossierPolicy,
        'owner' => $owner,
        'tenant' => $tenant,
        'dossier' => $dossier,
    ];
}

test('owner can viewAny, view, create, update, and delete a draft dossier', function () {
    $context = dossierPolicyContext();
    $policy = $context['policy'];
    $owner = $context['owner'];
    $dossier = $context['dossier'];

    expect($policy->viewAny($owner))->toBeTrue()
        ->and($policy->view($owner, $dossier))->toBeTrue()
        ->and($policy->create($owner))->toBeTrue()
        ->and($policy->update($owner, $dossier))->toBeTrue()
        ->and($policy->delete($owner, $dossier))->toBeTrue();
});

test('update and complete are denied when the dossier is completed', function () {
    $context = dossierPolicyContext();
    $policy = $context['policy'];
    $owner = $context['owner'];
    $dossier = $context['dossier'];

    $dossier->forceFill(['status' => DossierStatus::Completed])->save();

    expect($policy->update($owner, $dossier))->toBeFalse()
        ->and($policy->complete($owner, $dossier))->toBeFalse()
        ->and($policy->delete($owner, $dossier))->toBeTrue();
});

test('read-only members can view dossiers but cannot create or complete', function () {
    $context = dossierPolicyContext();
    $policy = $context['policy'];
    $tenant = $context['tenant'];
    $dossier = $context['dossier'];

    $reader = workspaceMember($tenant, Role::ReadOnly);

    expect($policy->viewAny($reader))->toBeTrue()
        ->and($policy->view($reader, $dossier))->toBeTrue()
        ->and($policy->create($reader))->toBeFalse()
        ->and($policy->complete($reader, $dossier))->toBeFalse();
});

test('users outside the tenant cannot view the dossier', function () {
    $context = dossierPolicyContext();
    $policy = $context['policy'];
    $dossier = $context['dossier'];
    $outsider = User::factory()->create();

    expect($policy->view($outsider, $dossier))->toBeFalse();
});
