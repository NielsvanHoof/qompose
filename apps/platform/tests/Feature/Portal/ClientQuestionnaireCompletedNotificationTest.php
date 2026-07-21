<?php

declare(strict_types=1);

use App\Actions\Portal\CreateClientAccessGrant;
use App\Actions\Portal\NotifyWorkspaceIfQuestionnaireComplete;
use App\Actions\Tenancy\ProvisionTenant;
use App\Enums\AuditEvent;
use App\Enums\DocumentRequestStatus;
use App\Enums\QuestionnaireItemType;
use App\Enums\TenantMembershipStatus;
use App\Events\Portal\ClientQuestionnaireCompleted;
use App\Models\Activity;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array{
 *     owner: User,
 *     tenant: mixed,
 *     client: Client,
 *     dossier: Dossier,
 *     plainTextToken: string
 * }
 */
function createQuestionnaireNotificationContext(): array
{
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();

    $client = Client::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Jane Client',
        'email' => 'jane@example.com',
    ]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => '2025 Payroll dossier',
    ]);

    $result = app(CreateClientAccessGrant::class)->handle($dossier, $owner, 7);

    return [
        'owner' => $owner,
        'tenant' => $tenant,
        'client' => $client,
        'dossier' => $dossier->fresh(),
        'plainTextToken' => $result['plain_text_token'],
    ];
}

test('last portal answer submission broadcasts questionnaire completed to the workspace', function () {
    Event::fake([ClientQuestionnaireCompleted::class]);

    $context = createQuestionnaireNotificationContext();

    $context['tenant']->makeCurrent();
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'type' => QuestionnaireItemType::Text,
        'title' => 'Registered address',
        'status' => DocumentRequestStatus::Pending,
    ]);

    $this->get(route('portal.exchange', $context['plainTextToken']));

    $this->post(route('portal.document-requests.answer', [
        'documentRequest' => $documentRequest,
    ]), [
        'answer_text' => '221B Baker Street',
    ])->assertRedirect(route('portal.show'));

    Event::assertDispatched(
        ClientQuestionnaireCompleted::class,
        function (ClientQuestionnaireCompleted $event) use ($context): bool {
            expect($event->tenantSlug)->toBe($context['tenant']->slug)
                ->and($event->dossierId)->toBe($context['dossier']->id)
                ->and($event->dossierTitle)->toBe('2025 Payroll dossier')
                ->and($event->clientName)->toBe('Jane Client')
                ->and($event->message)->toContain('Jane Client')
                ->and($event->dossierUrl)->toContain($context['tenant']->slug);

            return true;
        },
    );

    expect(
        Activity::query()
            ->where('event', AuditEvent::ClientQuestionnaireCompleted->value)
            ->where('subject_id', $context['dossier']->id)
            ->exists(),
    )->toBeTrue();
});

test('partial portal submission does not broadcast questionnaire completed', function () {
    Event::fake([ClientQuestionnaireCompleted::class]);

    $context = createQuestionnaireNotificationContext();

    $context['tenant']->makeCurrent();
    $first = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'type' => QuestionnaireItemType::Text,
        'title' => 'Address',
        'status' => DocumentRequestStatus::Pending,
        'sort_order' => 0,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'type' => QuestionnaireItemType::Boolean,
        'title' => 'Self employed?',
        'status' => DocumentRequestStatus::Pending,
        'sort_order' => 1,
    ]);

    $this->get(route('portal.exchange', $context['plainTextToken']));

    $this->post(route('portal.document-requests.answer', [
        'documentRequest' => $first,
    ]), [
        'answer_text' => '221B Baker Street',
    ])->assertRedirect(route('portal.show'));

    Event::assertNotDispatched(ClientQuestionnaireCompleted::class);

    expect(
        Activity::query()
            ->where('event', AuditEvent::ClientQuestionnaireCompleted->value)
            ->exists(),
    )->toBeFalse();
});

test('empty dossier does not broadcast questionnaire completed', function () {
    Event::fake([ClientQuestionnaireCompleted::class]);

    $context = createQuestionnaireNotificationContext();

    $context['tenant']->makeCurrent();

    app(NotifyWorkspaceIfQuestionnaireComplete::class)->handle($context['dossier']);

    Event::assertNotDispatched(ClientQuestionnaireCompleted::class);
});

test('resubmitting after rejection broadcasts questionnaire completed again', function () {
    Event::fake([ClientQuestionnaireCompleted::class]);

    $context = createQuestionnaireNotificationContext();

    $context['tenant']->makeCurrent();
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'type' => QuestionnaireItemType::Text,
        'title' => 'Registered address',
        'status' => DocumentRequestStatus::Rejected,
    ]);

    $this->get(route('portal.exchange', $context['plainTextToken']));

    $this->post(route('portal.document-requests.answer', [
        'documentRequest' => $documentRequest,
    ]), [
        'answer_text' => 'Updated address',
    ])->assertRedirect(route('portal.show'));

    Event::assertDispatched(ClientQuestionnaireCompleted::class);
});

test('last portal submission creates database notifications for active members only', function () {
    Event::fake([ClientQuestionnaireCompleted::class]);

    $context = createQuestionnaireNotificationContext();
    $activeMember = User::factory()->create();
    $suspendedMember = User::factory()->create();
    $outsider = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $activeMember->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);
    TenantMembership::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $suspendedMember->id,
        'status' => TenantMembershipStatus::Suspended,
        'joined_at' => now(),
    ]);

    $context['tenant']->makeCurrent();
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'type' => QuestionnaireItemType::Text,
        'title' => 'Registered address',
        'status' => DocumentRequestStatus::Pending,
    ]);

    $this->get(route('portal.exchange', $context['plainTextToken']));

    $this->post(route('portal.document-requests.answer', [
        'documentRequest' => $documentRequest,
    ]), [
        'answer_text' => '221B Baker Street',
    ])->assertRedirect(route('portal.show'));

    expect($context['owner']->notifications)->toHaveCount(1)
        ->and($activeMember->notifications)->toHaveCount(1)
        ->and($suspendedMember->notifications)->toHaveCount(0)
        ->and($outsider->notifications)->toHaveCount(0);

    $payload = $context['owner']->notifications->first()->data;

    expect($payload['type'])->toBe('client_questionnaire_completed')
        ->and($payload['tenant_id'])->toBe((string) $context['tenant']->id)
        ->and($payload['dossier_id'])->toBe($context['dossier']->id)
        ->and($payload['client_name'])->toBe('Jane Client')
        ->and($payload['message'])->toContain('Jane Client')
        ->and($payload['dossier_url'])->toContain($context['tenant']->slug);
});

test('partial portal submission does not create database notifications', function () {
    Event::fake([ClientQuestionnaireCompleted::class]);
    Notification::fake();

    $context = createQuestionnaireNotificationContext();

    $context['tenant']->makeCurrent();
    $first = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'type' => QuestionnaireItemType::Text,
        'title' => 'Address',
        'status' => DocumentRequestStatus::Pending,
        'sort_order' => 0,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'type' => QuestionnaireItemType::Boolean,
        'title' => 'Self employed?',
        'status' => DocumentRequestStatus::Pending,
        'sort_order' => 1,
    ]);

    $this->get(route('portal.exchange', $context['plainTextToken']));

    $this->post(route('portal.document-requests.answer', [
        'documentRequest' => $first,
    ]), [
        'answer_text' => '221B Baker Street',
    ])->assertRedirect(route('portal.show'));

    Notification::assertNothingSent();
});
