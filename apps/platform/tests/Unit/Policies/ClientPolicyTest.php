<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use App\Policies\Clients\ClientPolicy;

test('owner can viewAny, view, create, and delete clients', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $policy = new ClientPolicy;

    expect($policy->viewAny($owner))->toBeTrue()
        ->and($policy->view($owner, $client))->toBeTrue()
        ->and($policy->create($owner))->toBeTrue()
        ->and($policy->delete($owner, $client))->toBeTrue();
});

test('read-only members cannot manage clients', function () {
    ['tenant' => $tenant] = provisionWorkspace();
    $reader = workspaceMember($tenant, Role::ReadOnly);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $policy = new ClientPolicy;

    expect($policy->viewAny($reader))->toBeFalse()
        ->and($policy->view($reader, $client))->toBeFalse()
        ->and($policy->create($reader))->toBeFalse()
        ->and($policy->delete($reader, $client))->toBeFalse();
});

test('users outside the tenant cannot view a client', function () {
    ['tenant' => $tenant] = provisionWorkspace();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $outsider = User::factory()->create();
    $policy = new ClientPolicy;

    expect($policy->view($outsider, $client))->toBeFalse();
});
