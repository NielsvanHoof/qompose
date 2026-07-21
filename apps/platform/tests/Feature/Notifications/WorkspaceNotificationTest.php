<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenantAction;
use App\Enums\TenantMembershipStatus;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\Portal\ClientQuestionnaireCompletedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array{owner: User, outsider: User, tenant: mixed, dossier: Dossier}
 */
function createWorkspaceNotificationContext(): array
{
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();

    $client = Client::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Jane Client',
    ]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => '2025 Payroll dossier',
    ]);

    return [
        'owner' => $owner,
        'outsider' => $outsider,
        'tenant' => $tenant,
        'dossier' => $dossier,
    ];
}

function notifyOwnerOfQuestionnaire(User $owner, mixed $tenant, Dossier $dossier): void
{
    $owner->notify(new ClientQuestionnaireCompletedNotification(
        tenantId: $tenant->id,
        dossierId: $dossier->id,
        dossierTitle: $dossier->title,
        clientName: 'Jane Client',
        message: 'Jane Client finished the questionnaire for “2025 Payroll dossier”.',
        dossierUrl: route('workspaces.dossiers.show', [
            'tenant' => $tenant,
            'dossier' => $dossier,
        ]),
    ));
}

test('shared notifications prop returns tenant-scoped inbox items', function () {
    $context = createWorkspaceNotificationContext();
    notifyOwnerOfQuestionnaire($context['owner'], $context['tenant'], $context['dossier']);

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->get(workspaceRoute('workspaces.dashboard', $context['tenant']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/dashboard')
            ->missing('notifications')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('notifications.unread_count', 1)
                ->has('notifications.items', 1)
                ->where('notifications.items.0.message', 'Jane Client finished the questionnaire for “2025 Payroll dossier”.')
                ->where('notifications.items.0.dossier_id', $context['dossier']->id)
                ->where('notifications.items.0.read_at', null)));
});

test('staff can mark one workspace notification as read', function () {
    $context = createWorkspaceNotificationContext();
    notifyOwnerOfQuestionnaire($context['owner'], $context['tenant'], $context['dossier']);

    $notification = $context['owner']->notifications()->sole();

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->from(workspaceRoute('workspaces.dashboard', $context['tenant']))
        ->post(workspaceRoute('workspaces.notifications.read', $context['tenant'], [
            'notification' => $notification->id,
        ]))
        ->assertRedirect(workspaceRoute('workspaces.dashboard', $context['tenant']));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('staff can mark all workspace notifications as read', function () {
    $context = createWorkspaceNotificationContext();
    notifyOwnerOfQuestionnaire($context['owner'], $context['tenant'], $context['dossier']);
    notifyOwnerOfQuestionnaire($context['owner'], $context['tenant'], $context['dossier']);

    expect($context['owner']->unreadNotifications()->count())->toBe(2);

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->from(workspaceRoute('workspaces.dashboard', $context['tenant']))
        ->post(workspaceRoute('workspaces.notifications.read-all', $context['tenant']))
        ->assertRedirect(workspaceRoute('workspaces.dashboard', $context['tenant']));

    expect($context['owner']->unreadNotifications()->count())->toBe(0);
});

test('staff cannot mark another users notification as read', function () {
    $context = createWorkspaceNotificationContext();
    notifyOwnerOfQuestionnaire($context['owner'], $context['tenant'], $context['dossier']);

    $notification = $context['owner']->notifications()->sole();

    TenantMembership::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $context['outsider']->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    $this->actingAs($context['outsider'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->post(workspaceRoute('workspaces.notifications.read', $context['tenant'], [
            'notification' => $notification->id,
        ]))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});
