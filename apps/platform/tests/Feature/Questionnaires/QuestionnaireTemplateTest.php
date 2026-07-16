<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenant;
use App\Actions\Portal\CreateClientAccessGrant;
use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Enums\QuestionnaireItemType;
use App\Enums\QuestionnaireTemplateCategory;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemQuestionnaireTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('staff can view system and firm templates', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $this->seed(SystemQuestionnaireTemplateSeeder::class);

    $tenant->makeCurrent();
    QuestionnaireTemplate::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Firm KYC copy',
        'category' => QuestionnaireTemplateCategory::Kyc,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(route('workspaces.templates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('questionnaires/index')
            ->has('system_templates', 4)
            ->has('firm_templates', 1)
            ->where('can_manage', true));
});

test('system templates cannot be updated or deleted', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $this->seed(SystemQuestionnaireTemplateSeeder::class);

    $system = QuestionnaireTemplate::query()->whereNull('tenant_id')->firstOrFail();

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('workspaces.templates.update', $system), [
            'name' => 'Hacked',
            'description' => 'Nope',
            'category' => QuestionnaireTemplateCategory::Custom->value,
        ])
        ->assertForbidden();

    $this->delete(route('workspaces.templates.destroy', $system))
        ->assertForbidden();

    expect($system->fresh()->name)->not->toBe('Hacked');
});

test('owner can copy a system template into the firm', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $this->seed(SystemQuestionnaireTemplateSeeder::class);

    $system = QuestionnaireTemplate::query()
        ->whereNull('tenant_id')
        ->where('category', QuestionnaireTemplateCategory::Kyc->value)
        ->firstOrFail();

    $itemCount = $system->items()->count();

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('workspaces.templates.copy', $system))
        ->assertRedirect();

    $tenant->makeCurrent();
    $copy = QuestionnaireTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('source_template_id', $system->id)
        ->sole();

    expect($copy->name)->toBe($system->name)
        ->and($copy->items()->count())->toBe($itemCount)
        ->and($copy->isSystem())->toBeFalse();

    // Second copy is allowed.
    $this->post(route('workspaces.templates.copy', $system))->assertRedirect();

    expect(QuestionnaireTemplate::query()
        ->where('tenant_id', $tenant->id)
        ->where('source_template_id', $system->id)
        ->count())->toBe(2);
});

test('read only staff cannot copy templates', function () {
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $reader->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    setPermissionsTeamId($tenant->id);
    $reader->assignRole(Role::ReadOnly->value);

    $this->seed(SystemQuestionnaireTemplateSeeder::class);
    $system = QuestionnaireTemplate::query()->whereNull('tenant_id')->firstOrFail();

    $this->actingAs($reader)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('workspaces.templates.copy', $system))
        ->assertForbidden();
});

test('adviser can apply a template and edit dossier items', function () {
    $owner = User::factory()->create();
    $adviser = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $adviser->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    setPermissionsTeamId($tenant->id);
    $adviser->assignRole(Role::Adviser->value);

    $this->seed(SystemQuestionnaireTemplateSeeder::class);

    $tenant->makeCurrent();
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'title' => 'Existing ad-hoc request',
        'sort_order' => 0,
    ]);

    $system = QuestionnaireTemplate::query()
        ->whereNull('tenant_id')
        ->where('category', QuestionnaireTemplateCategory::Kyc->value)
        ->firstOrFail();

    $this->actingAs($adviser)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('workspaces.dossiers.apply-template', $dossier), [
            'questionnaire_template_id' => $system->id,
        ])
        ->assertRedirect(route('workspaces.dossiers.show', $dossier));

    $tenant->makeCurrent();
    $requests = DocumentRequest::query()
        ->where('dossier_id', $dossier->id)
        ->orderBy('sort_order')
        ->get();

    expect($requests)->toHaveCount(1 + $system->items()->count())
        ->and($requests->first()->title)->toBe('Existing ad-hoc request')
        ->and($requests->pluck('type')->contains(QuestionnaireItemType::Boolean))->toBeTrue();

    $firstTemplateItem = $requests->skip(1)->first();

    $this->put(route('workspaces.dossiers.document-requests.update', [
        'dossier' => $dossier,
        'documentRequest' => $firstTemplateItem,
    ]), [
        'type' => QuestionnaireItemType::Text->value,
        'title' => 'Customised title',
        'instructions' => 'Updated instructions',
    ])->assertRedirect(route('workspaces.dossiers.show', $dossier));

    expect($firstTemplateItem->fresh()->title)->toBe('Customised title');

    $this->delete(route('workspaces.dossiers.document-requests.destroy', [
        'dossier' => $dossier,
        'documentRequest' => $firstTemplateItem,
    ]))->assertRedirect(route('workspaces.dossiers.show', $dossier));

    expect(DocumentRequest::query()->whereKey($firstTemplateItem->id)->exists())->toBeFalse();
});

test('firm templates are isolated between tenants', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();
    $tenantA = app(ProvisionTenant::class)('Tenant A', $ownerA);
    $tenantB = app(ProvisionTenant::class)('Tenant B', $ownerB);

    $tenantA->makeCurrent();
    $templateA = QuestionnaireTemplate::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Secret A',
    ]);

    $this->actingAs($ownerB)
        ->withSession(['active_tenant_id' => $tenantB->id])
        ->get(route('workspaces.templates.show', $templateA))
        ->assertNotFound();
});

test('owner can manage firm template items', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $tenant->makeCurrent();
    $template = QuestionnaireTemplate::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Custom pack',
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('workspaces.templates.items.store', $template), [
            'type' => QuestionnaireItemType::Boolean->value,
            'title' => 'Confirm completeness',
            'instructions' => 'Yes or no',
        ])
        ->assertRedirect(route('workspaces.templates.show', $template));

    $item = QuestionnaireTemplateItem::query()->where('questionnaire_template_id', $template->id)->sole();

    expect($item->type)->toBe(QuestionnaireItemType::Boolean)
        ->and($item->title)->toBe('Confirm completeness');
});

test('guest can submit text and boolean answers through the portal', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $tenant->makeCurrent();
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::AwaitingClient,
    ]);

    $textRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'type' => QuestionnaireItemType::Text,
        'title' => 'Address',
        'status' => DocumentRequestStatus::Pending,
    ]);
    $booleanRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'type' => QuestionnaireItemType::Boolean,
        'title' => 'UBO confirmed',
        'status' => DocumentRequestStatus::Pending,
    ]);

    $result = app(CreateClientAccessGrant::class)($dossier, $owner, 7);
    $plainTextToken = $result['plain_text_token'];
    $grant = $result['grant'];

    $this->post(route('portal.document-requests.answer', [
        'token' => $plainTextToken,
        'documentRequest' => $textRequest->id,
    ]), [
        'answer_text' => 'Keizersgracht 1, Amsterdam',
    ])->assertRedirect(route('portal.show', $plainTextToken));

    $this->post(route('portal.document-requests.answer', [
        'token' => $plainTextToken,
        'documentRequest' => $booleanRequest->id,
    ]), [
        'answer_boolean' => true,
    ])->assertRedirect(route('portal.show', $plainTextToken));

    $tenant->makeCurrent();

    expect($textRequest->fresh())
        ->status->toBe(DocumentRequestStatus::Submitted)
        ->answer_text->toBe('Keizersgracht 1, Amsterdam')
        ->and($booleanRequest->fresh())
        ->status->toBe(DocumentRequestStatus::Submitted)
        ->answer_boolean->toBeTrue()
        ->and($dossier->fresh()->status)->toBe(DossierStatus::InReview)
        ->and($grant->fresh()->last_used_at)->not->toBeNull();
});
