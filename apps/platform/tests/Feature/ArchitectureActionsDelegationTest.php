<?php

declare(strict_types=1);

use App\Actions\Clients\UpdateClientAction;
use App\Actions\Dossiers\Builder\UpdateDocumentRequestAction;
use App\Actions\Portal\RecordClientPortalAccessAction;
use App\Actions\Questionnaires\DeleteQuestionnaireTemplateAction;
use App\Actions\Questionnaires\DeleteQuestionnaireTemplateItemAction;
use App\Actions\Questionnaires\UpdateQuestionnaireTemplateAction;
use App\Actions\Questionnaires\UpdateQuestionnaireTemplateItemAction;
use App\Actions\Tenancy\ProvisionTenantAction;
use App\Enums\AuditEvent;
use App\Enums\QuestionnaireItemType;
use App\Enums\QuestionnaireTemplateCategory;
use App\Models\Activity;
use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use App\Models\User;
use App\Queries\Dossiers\TenantHasAnyDossiersQuery;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('update client action persists profile attributes', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);
    $tenant->makeCurrent();

    $client = Client::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Jane',
        'email' => 'jane@example.com',
    ]);

    app(UpdateClientAction::class)->handle($client, [
        'name' => 'Jane Updated',
        'email' => 'jane.updated@example.com',
    ]);

    expect($client->fresh())
        ->name->toBe('Jane Updated')
        ->email->toBe('jane.updated@example.com');
});

test('tenant has any dossiers query reports emptiness for a fresh workspace', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);
    $tenant->makeCurrent();

    expect(app(TenantHasAnyDossiersQuery::class)->handle())->toBeFalse();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    expect(app(TenantHasAnyDossiersQuery::class)->handle())->toBeTrue();
});

test('record client portal access stamps grant dossier and audit', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);
    $tenant->makeCurrent();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'last_client_opened_at' => null,
    ]);
    $grant = ClientAccessGrant::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'created_by' => $owner->id,
        'last_used_at' => null,
    ]);

    app(RecordClientPortalAccessAction::class)->handle($grant);

    expect($grant->fresh()->last_used_at)->not->toBeNull()
        ->and($dossier->fresh()->last_client_opened_at)->not->toBeNull()
        ->and(Activity::query()
            ->where('event', AuditEvent::ClientPortalAccessed->value)
            ->where('subject_id', $grant->id)
            ->exists())->toBeTrue();
});

test('update document request action persists builder attributes', function () {
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
        'title' => 'Old title',
    ]);

    app(UpdateDocumentRequestAction::class)->handle($documentRequest, [
        'type' => QuestionnaireItemType::Text->value,
        'title' => 'New title',
        'instructions' => 'Please answer',
    ]);

    expect($documentRequest->fresh())
        ->title->toBe('New title')
        ->instructions->toBe('Please answer')
        ->type->toBe(QuestionnaireItemType::Text);
});

test('questionnaire template update and delete actions persist and audit', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);
    $tenant->makeCurrent();

    $template = QuestionnaireTemplate::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Custom pack',
        'category' => QuestionnaireTemplateCategory::Custom,
    ]);

    app(UpdateQuestionnaireTemplateAction::class)->handle($template, [
        'name' => 'Renamed pack',
        'description' => 'Updated',
        'category' => QuestionnaireTemplateCategory::Kyc->value,
    ]);

    expect($template->fresh()->name)->toBe('Renamed pack');

    app(DeleteQuestionnaireTemplateAction::class)->handle($template);

    expect(QuestionnaireTemplate::query()->whereKey($template->id)->exists())->toBeFalse()
        ->and(Activity::query()
            ->where('event', AuditEvent::QuestionnaireTemplateDeleted->value)
            ->where('subject_id', $template->id)
            ->exists())->toBeTrue();
});

test('questionnaire template item update and delete actions persist and audit', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);
    $tenant->makeCurrent();

    $template = QuestionnaireTemplate::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Custom pack',
    ]);
    $item = QuestionnaireTemplateItem::factory()->create([
        'questionnaire_template_id' => $template->id,
        'title' => 'Old item',
    ]);

    app(UpdateQuestionnaireTemplateItemAction::class)->handle($item, [
        'type' => QuestionnaireItemType::Text->value,
        'title' => 'Updated item',
        'instructions' => null,
    ]);

    expect($item->fresh()->title)->toBe('Updated item');

    app(DeleteQuestionnaireTemplateItemAction::class)->handle($template, $item);

    expect(QuestionnaireTemplateItem::query()->whereKey($item->id)->exists())->toBeFalse()
        ->and(Activity::query()
            ->where('event', AuditEvent::QuestionnaireTemplateItemDeleted->value)
            ->where('subject_id', $item->id)
            ->exists())->toBeTrue();
});
