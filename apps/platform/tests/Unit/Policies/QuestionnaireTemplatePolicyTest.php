<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\QuestionnaireTemplate;
use App\Policies\Questionnaires\QuestionnaireTemplatePolicy;

test('owner can manage firm templates and copy system templates', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();

    $firmTemplate = QuestionnaireTemplate::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $systemTemplate = QuestionnaireTemplate::factory()->system()->create();
    $policy = new QuestionnaireTemplatePolicy;

    expect($policy->viewAny($owner))->toBeTrue()
        ->and($policy->view($owner, $firmTemplate))->toBeTrue()
        ->and($policy->view($owner, $systemTemplate))->toBeTrue()
        ->and($policy->create($owner))->toBeTrue()
        ->and($policy->update($owner, $firmTemplate))->toBeTrue()
        ->and($policy->update($owner, $systemTemplate))->toBeFalse()
        ->and($policy->copy($owner, $systemTemplate))->toBeTrue()
        ->and($policy->apply($owner, $systemTemplate))->toBeTrue();
});

test('read-only members can view templates but cannot manage them', function () {
    ['tenant' => $tenant] = provisionWorkspace();
    $reader = workspaceMember($tenant, Role::ReadOnly);

    $firmTemplate = QuestionnaireTemplate::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $policy = new QuestionnaireTemplatePolicy;

    expect($policy->viewAny($reader))->toBeTrue()
        ->and($policy->view($reader, $firmTemplate))->toBeTrue()
        ->and($policy->create($reader))->toBeFalse()
        ->and($policy->update($reader, $firmTemplate))->toBeFalse()
        ->and($policy->copy($reader, $firmTemplate))->toBeFalse();
});
