<?php

declare(strict_types=1);

use App\Actions\Portal\CreateClientAccessGrantAction;
use App\Actions\Tenancy\ProvisionTenantAction;
use App\Enums\AuditEvent;
use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Enums\QuestionnaireItemType;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Listeners\LogClientChangesRequestedFailed;
use App\Listeners\LogClientChangesRequestedSent;
use App\Models\Activity;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\TenantMembership;
use App\Models\UploadedDocument;
use App\Models\User;
use App\Notifications\Portal\ClientChangesRequestedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array{owner: User, reviewer: User, tenant: mixed, dossier: Dossier}
 */
function createReviewWorkflowContext(): array
{
    $owner = User::factory()->create();
    $reviewer = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $reviewer->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);
    $reviewer->assignRole(Role::Reviewer->value);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::InReview,
    ]);

    return [
        'owner' => $owner,
        'reviewer' => $reviewer,
        'tenant' => $tenant,
        'dossier' => $dossier,
    ];
}

test('a reviewer can approve submitted evidence without editing the questionnaire', function () {
    Notification::fake();

    $context = createReviewWorkflowContext();
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'status' => DocumentRequestStatus::Submitted,
    ]);
    $uploadedDocument = UploadedDocument::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'document_request_id' => $documentRequest->id,
    ]);

    $this->actingAs($context['reviewer'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->post(workspaceRoute('workspaces.dossiers.document-requests.review', $context['tenant'], [
            'dossier' => $context['dossier'],
            'documentRequest' => $documentRequest,
        ]), [
            'decision' => DocumentRequestStatus::Accepted->value,
        ])
        ->assertRedirect(workspaceRoute('workspaces.dossiers.show', $context['tenant'], [
            'dossier' => $context['dossier'],
        ]));

    expect($documentRequest->fresh()->status)->toBe(DocumentRequestStatus::Accepted)
        ->and($documentRequest->fresh()->reviewed_by)->toBe($context['reviewer']->id)
        ->and($documentRequest->fresh()->reviewed_at)->not->toBeNull()
        ->and($uploadedDocument->fresh()->reviewed_by)->toBe($context['reviewer']->id)
        ->and($uploadedDocument->fresh()->reviewed_at)->not->toBeNull();

    $activity = Activity::query()
        ->where('event', AuditEvent::DocumentRequestAccepted->value)
        ->sole();

    expect($activity->subject_id)->toBe($documentRequest->id)
        ->and($activity->causer_id)->toBe($context['reviewer']->id)
        ->and($activity->getProperty('decision'))->toBe(DocumentRequestStatus::Accepted->value);

    $this->put(workspaceRoute('workspaces.dossiers.document-requests.update', $context['tenant'], [
        'dossier' => $context['dossier'],
        'documentRequest' => $documentRequest,
    ]), [
        'type' => QuestionnaireItemType::File->value,
        'title' => 'Reviewer may not rewrite this',
    ])->assertForbidden();

    Notification::assertNothingSent();
});

test('rejected items show feedback and a client correction returns them to review', function () {
    Notification::fake();

    $context = createReviewWorkflowContext();
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'type' => QuestionnaireItemType::Text,
        'answer_text' => 'Incomplete answer',
        'answered_at' => now(),
        'status' => DocumentRequestStatus::Submitted,
    ]);

    $grantResult = app(CreateClientAccessGrantAction::class)->handle(
        $context['dossier'],
        $context['owner'],
        7,
    );

    $reviewUrl = workspaceRoute(
        'workspaces.dossiers.document-requests.review',
        $context['tenant'],
        [
            'dossier' => $context['dossier'],
            'documentRequest' => $documentRequest,
        ],
    );

    $this->actingAs($context['reviewer'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->post($reviewUrl, [
            'decision' => DocumentRequestStatus::Rejected->value,
        ])
        ->assertSessionHasErrors('rejection_reason');

    expect($documentRequest->fresh()->status)->toBe(DocumentRequestStatus::Submitted);

    $this->post($reviewUrl, [
        'decision' => DocumentRequestStatus::Rejected->value,
        'rejection_reason' => 'Please include the full registered address.',
    ])->assertRedirect();

    expect($documentRequest->fresh()->status)->toBe(DocumentRequestStatus::Rejected)
        ->and($documentRequest->fresh()->rejection_reason)
        ->toBe('Please include the full registered address.');

    Notification::assertSentOnDemand(
        ClientChangesRequestedNotification::class,
        function (
            ClientChangesRequestedNotification $notification,
            array $channels,
            AnonymousNotifiable $notifiable,
        ) use ($context, $documentRequest): bool {
            $queuedJob = new SendQueuedNotifications($notifiable, $notification, $channels);
            $mail = $notification->toMail($notifiable);

            expect($notification)->toBeInstanceOf(ShouldBeEncrypted::class)
                ->and($notification->afterCommit)->toBeTrue()
                ->and($queuedJob->afterCommit)->toBeTrue()
                ->and($queuedJob->shouldBeEncrypted)->toBeTrue()
                ->and($notification->documentRequestId)->toBe($documentRequest->id)
                ->and($notification->dossierId)->toBe($context['dossier']->id)
                ->and($mail->subject)->toContain('changes requested')
                ->and((string) json_encode($mail->introLines))
                ->toContain('original invitation')
                ->not->toContain('Please include the full registered address.');

            return true;
        },
    );

    expect(Activity::query()
        ->where('event', AuditEvent::ClientChangesRequestedQueued->value)
        ->where('subject_id', $documentRequest->id)
        ->exists())->toBeTrue();

    $this->get(route('portal.exchange', $grantResult['plain_text_token']))
        ->assertRedirect(route('portal.show'));

    $this->get(route('portal.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where(
                'dossier.document_requests.0.rejection_reason',
                'Please include the full registered address.',
            ));

    $answerUrl = route('portal.document-requests.answer', [
        'documentRequest' => $documentRequest,
    ]);

    $this->post($answerUrl, [
        'answer_text' => '221B Baker Street, London',
    ])->assertRedirect(route('portal.show'));

    $correctedRequest = $documentRequest->fresh();

    expect($correctedRequest->status)->toBe(DocumentRequestStatus::Submitted)
        ->and($correctedRequest->answer_text)->toBe('221B Baker Street, London')
        ->and($correctedRequest->reviewed_by)->toBeNull()
        ->and($correctedRequest->reviewed_at)->toBeNull()
        ->and($correctedRequest->rejection_reason)->toBeNull();

    $this->post($answerUrl, [
        'answer_text' => 'Changing an item already under review',
    ])->assertForbidden();
});

test('changes requested delivery outcomes are audited', function () {
    $context = createReviewWorkflowContext();
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'status' => DocumentRequestStatus::Rejected,
        'rejection_reason' => 'Please correct this.',
    ]);
    $notification = new ClientChangesRequestedNotification(
        documentRequestId: $documentRequest->id,
        dossierId: $context['dossier']->id,
        clientName: 'Jane Client',
        dossierTitle: $context['dossier']->title,
        documentRequestTitle: $documentRequest->title,
        firmName: $context['tenant']->name,
    );
    $notifiable = (new AnonymousNotifiable)->route('mail', 'jane@example.com');

    app(LogClientChangesRequestedSent::class)->handle(
        new NotificationSent($notifiable, $notification, 'mail'),
    );
    app(LogClientChangesRequestedFailed::class)->handle(
        new NotificationFailed($notifiable, $notification, 'mail', [
            'exception' => new RuntimeException('Mail failed.'),
        ]),
    );

    $activities = Activity::query()
        ->whereIn('event', [
            AuditEvent::ClientChangesRequestedSent->value,
            AuditEvent::ClientChangesRequestedFailed->value,
        ])
        ->get();

    expect($activities)->toHaveCount(2)
        ->and($activities->pluck('event'))->toContain(
            AuditEvent::ClientChangesRequestedSent->value,
            AuditEvent::ClientChangesRequestedFailed->value,
        )
        ->and($activities->pluck('subject_id')->unique()->all())
        ->toBe([$documentRequest->id]);
});

test('a dossier can only be completed after every item is approved', function () {
    $context = createReviewWorkflowContext();
    $documentRequestQueries = [];

    DB::listen(function (QueryExecuted $query) use (&$documentRequestQueries): void {
        if (str_contains($query->sql, 'document_requests')) {
            $documentRequestQueries[] = $query->sql;
        }
    });

    $acceptedRequest = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'status' => DocumentRequestStatus::Accepted,
        'reviewed_by' => $context['reviewer']->id,
        'reviewed_at' => now(),
    ]);
    $rejectedRequest = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'type' => QuestionnaireItemType::Text,
        'status' => DocumentRequestStatus::Rejected,
        'rejection_reason' => 'Please correct this.',
        'reviewed_by' => $context['reviewer']->id,
        'reviewed_at' => now(),
    ]);

    $grantResult = app(CreateClientAccessGrantAction::class)->handle(
        $context['dossier'],
        $context['owner'],
        7,
    );

    $completeUrl = workspaceRoute('workspaces.dossiers.complete', $context['tenant'], [
        'dossier' => $context['dossier'],
    ]);

    $this->actingAs($context['reviewer'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->post($completeUrl)
        ->assertSessionHasErrors('dossier');

    expect($context['dossier']->fresh()->status)->toBe(DossierStatus::InReview);

    $rejectedRequest->update([
        'status' => DocumentRequestStatus::Accepted,
        'rejection_reason' => null,
    ]);

    $this->post($completeUrl)
        ->assertRedirect(workspaceRoute('workspaces.dossiers.show', $context['tenant'], [
            'dossier' => $context['dossier'],
        ]));

    expect($context['dossier']->fresh()->status)->toBe(DossierStatus::Completed)
        ->and(Activity::query()
            ->where('event', AuditEvent::DossierCompleted->value)
            ->where('subject_id', $context['dossier']->id)
            ->exists())->toBeTrue()
        ->and(collect($documentRequestQueries)->contains(
            fn (string $query): bool => str_contains($query, 'count(*)'),
        ))->toBeFalse();

    $this->get(route('portal.exchange', $grantResult['plain_text_token']));

    $this->post(route('portal.document-requests.answer', [
        'documentRequest' => $rejectedRequest,
    ]), [
        'answer_text' => 'A late change',
    ])->assertForbidden();

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->post(workspaceRoute('workspaces.dossiers.document-requests.store', $context['tenant'], [
            'dossier' => $context['dossier'],
        ]), [
            'type' => QuestionnaireItemType::File->value,
            'title' => 'Late request',
        ])
        ->assertForbidden();

    expect($acceptedRequest->fresh()->status)->toBe(DocumentRequestStatus::Accepted);
});
