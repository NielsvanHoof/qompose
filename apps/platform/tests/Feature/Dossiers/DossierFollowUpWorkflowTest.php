<?php

declare(strict_types=1);

use App\Actions\Portal\CreateClientAccessGrantAction;
use App\Enums\AuditEvent;
use App\Enums\DocumentRequestStatus;
use App\Enums\DossierReminderSource;
use App\Enums\DossierStatus;
use App\Enums\Role;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Listeners\LogClientPortalReminderSent;
use App\Models\Activity;
use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\User;
use App\Notifications\Portal\ClientPortalReminderNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('dossier follow up details can be assigned only to active workspace staff', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();
    $adviser = workspaceMember($tenant, Role::Adviser);
    $foreignUser = User::factory()->create();
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->patch(workspaceRoute('workspaces.dossiers.update', $tenant, ['dossier' => $dossier]), [
            'title' => $dossier->title,
            'reference' => $dossier->reference,
            'due_date' => '2026-08-15',
            'responsible_user_id' => $adviser->id,
            'reminder_interval_days' => 3,
        ])
        ->assertRedirect(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]));

    $updatedDossier = $dossier->fresh();

    expect($updatedDossier->due_date?->toDateString())->toBe('2026-08-15')
        ->and($updatedDossier->responsible_user_id)->toBe($adviser->id)
        ->and($updatedDossier->reminder_interval_days)->toBe(3);

    $this->patch(workspaceRoute('workspaces.dossiers.update', $tenant, ['dossier' => $dossier]), [
        'title' => $dossier->title,
        'reference' => $dossier->reference,
        'responsible_user_id' => $foreignUser->id,
    ])->assertSessionHasErrors('responsible_user_id');
});

test('dashboard and dossier index expose operational workflow queues', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $needsReview = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::InReview,
    ]);
    $awaitingClient = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'due_date' => today()->addWeek(),
    ]);
    $overdue = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => 'Overdue payroll',
        'due_date' => today()->subDay(),
    ]);

    DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $needsReview->id,
        'status' => DocumentRequestStatus::Submitted,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $awaitingClient->id,
        'status' => DocumentRequestStatus::Pending,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $overdue->id,
        'status' => DocumentRequestStatus::Rejected,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dashboard', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('metrics.needs_review', 1)
            ->where('metrics.awaiting_client', 2)
            ->where('metrics.overdue', 1));

    $this->get(workspaceRoute('workspaces.dossiers.index', $tenant).'?filter[queue]=overdue')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dossiers.data', 1)
            ->where('dossiers.data.0.title', 'Overdue payroll'));
});

test('staff can queue an audited reminder with a fresh portal grant', function () {
    Notification::fake();

    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();
    $client = Client::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'jane@example.com',
    ]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::AwaitingClient,
        'reminder_interval_days' => 3,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'status' => DocumentRequestStatus::Pending,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(workspaceRoute('workspaces.dossiers.reminders.store', $tenant, [
            'dossier' => $dossier,
        ]))
        ->assertRedirect(workspaceRoute('workspaces.dossiers.show', $tenant, [
            'dossier' => $dossier,
        ]));

    $grant = ClientAccessGrant::query()->sole();
    $notification = null;

    Notification::assertSentOnDemand(
        ClientPortalReminderNotification::class,
        function (ClientPortalReminderNotification $sentNotification) use (&$notification): bool {
            $notification = $sentNotification;

            return true;
        },
    );

    expect($dossier->fresh()->next_reminder_at?->isFuture())->toBeTrue();

    $queuedActivity = Activity::query()
        ->where('event', AuditEvent::DossierReminderQueued->value)
        ->sole();

    expect($queuedActivity->subject_id)->toBe($dossier->id)
        ->and($queuedActivity->causer_id)->toBe($owner->id)
        ->and($queuedActivity->getProperty('source'))->toBe(DossierReminderSource::Manual->value)
        ->and((string) json_encode($queuedActivity->properties))->not->toContain($grant->token);

    expect($notification)->toBeInstanceOf(ClientPortalReminderNotification::class);
    assert($notification instanceof ClientPortalReminderNotification);

    app(LogClientPortalReminderSent::class)->handle(
        new NotificationSent(new AnonymousNotifiable, $notification, 'mail'),
    );

    expect($dossier->fresh()->last_client_message_sent_at)->not->toBeNull()
        ->and(Activity::query()
            ->where('event', AuditEvent::DossierReminderSent->value)
            ->where('subject_id', $dossier->id)
            ->exists())->toBeTrue();
});

test('scheduled reminders process only due dossiers with client work remaining', function () {
    Notification::fake();

    ['tenant' => $tenant] = provisionWorkspace();
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dueDossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::AwaitingClient,
        'reminder_interval_days' => 2,
        'next_reminder_at' => now()->subMinute(),
    ]);
    $laterDossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'status' => DossierStatus::AwaitingClient,
        'reminder_interval_days' => 2,
        'next_reminder_at' => now()->addDay(),
    ]);

    foreach ([$dueDossier, $laterDossier] as $dossier) {
        DocumentRequest::factory()->create([
            'tenant_id' => $tenant->id,
            'dossier_id' => $dossier->id,
            'status' => DocumentRequestStatus::Pending,
        ]);
    }

    $this->artisan('dossiers:send-reminders')->assertSuccessful();

    Notification::assertCount(1);
    expect(ClientAccessGrant::query()->whereBelongsTo($dueDossier)->count())->toBe(1)
        ->and(ClientAccessGrant::query()->whereBelongsTo($laterDossier)->count())->toBe(0)
        ->and(Activity::query()
            ->where('event', AuditEvent::DossierReminderQueued->value)
            ->where('subject_id', $dueDossier->id)
            ->whereNull('causer_id')
            ->exists())->toBeTrue();
});

test('portal reports progress and records the latest client open', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'due_date' => today()->addWeek(),
    ]);
    $pending = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'title' => 'Bank statement',
        'status' => DocumentRequestStatus::Pending,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'status' => DocumentRequestStatus::Submitted,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
        'status' => DocumentRequestStatus::Accepted,
    ]);
    $grant = app(CreateClientAccessGrantAction::class)->handle($dossier, $owner, 7);

    $this->get(route('portal.exchange', $grant['plain_text_token']))
        ->assertRedirect(route('portal.show'))
        ->assertSessionHas(ResolveClientPortalGrant::SESSION_GRANT_ID);

    $this->get(route('portal.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dossier.progress.total', 3)
            ->where('dossier.progress.completed', 2)
            ->where('dossier.progress.remaining', 1)
            ->where('dossier.progress.next_incomplete.id', $pending->id)
            ->where('dossier.progress.next_incomplete.title', 'Bank statement'));

    expect($dossier->fresh()->last_client_opened_at)->not->toBeNull();
});

test('read only members cannot send client reminders', function () {
    ['tenant' => $tenant] = provisionWorkspace();
    $reader = workspaceMember($tenant, Role::ReadOnly);
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);
    DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
    ]);

    $this->actingAs($reader)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(workspaceRoute('workspaces.dossiers.reminders.store', $tenant, [
            'dossier' => $dossier,
        ]))
        ->assertForbidden();
});

test('archiving a dossier cancels its scheduled reminder', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'reminder_interval_days' => 3,
        'next_reminder_at' => now()->addDay(),
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete(workspaceRoute('workspaces.dossiers.destroy', $tenant, [
            'dossier' => $dossier,
        ]))
        ->assertRedirect(workspaceRoute('workspaces.dossiers.index', $tenant));

    expect(Dossier::withTrashed()->findOrFail($dossier->id)->next_reminder_at)->toBeNull();
});
