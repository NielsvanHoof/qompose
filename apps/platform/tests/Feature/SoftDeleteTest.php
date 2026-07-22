<?php

declare(strict_types=1);

use App\Actions\Portal\CreateClientAccessGrantAction;
use App\Enums\AuditEvent;
use App\Enums\DossierStatus;
use App\Enums\Role;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array{owner: User, tenant: mixed, client: Client, dossier: Dossier}
 */
function softDeleteContext(): array
{
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => 'Annual accounts 2025',
    ]);

    return compact('owner', 'tenant', 'client', 'dossier');
}

test('owner can archive a dossier and it disappears from the index', function () {
    $context = softDeleteContext();

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->delete(workspaceRoute('workspaces.dossiers.destroy', $context['tenant'], [
            'dossier' => $context['dossier'],
        ]))
        ->assertRedirect(workspaceRoute('workspaces.dossiers.index', $context['tenant']));

    expect($context['dossier']->fresh()->trashed())->toBeTrue();

    $this->get(workspaceRoute('workspaces.dossiers.index', $context['tenant']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dossiers.data', 0)
            ->where('dossiers.total', 0));

    $this->get(workspaceRoute('workspaces.dossiers.show', $context['tenant'], [
        'dossier' => $context['dossier'],
    ]))->assertNotFound();
});

test('archiving a dossier logs a tenant scoped audit entry', function () {
    $context = softDeleteContext();

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->delete(workspaceRoute('workspaces.dossiers.destroy', $context['tenant'], [
            'dossier' => $context['dossier'],
        ]));

    $activity = Activity::query()
        ->where('event', AuditEvent::DossierDeleted->value)
        ->where('subject_id', $context['dossier']->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->tenant_id)->toBe($context['tenant']->id)
        ->and($activity->causer_id)->toBe($context['owner']->id)
        ->and($activity->getProperty('title'))->toBe('Annual accounts 2025');
});

test('archiving a dossier revokes active portal grants', function () {
    $context = softDeleteContext();

    $result = app(CreateClientAccessGrantAction::class)->handle(
        $context['dossier'],
        $context['owner'],
    );
    $grant = $result['grant'];

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->delete(workspaceRoute('workspaces.dossiers.destroy', $context['tenant'], [
            'dossier' => $context['dossier'],
        ]));

    expect($grant->fresh()->revoked_at)->not->toBeNull();
});

test('owner can archive a client and cascade soft delete to dossiers', function () {
    $context = softDeleteContext();

    $secondDossier = Dossier::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'client_id' => $context['client']->id,
        'title' => 'VAT 2025',
        'status' => DossierStatus::Completed,
    ]);

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->delete(workspaceRoute('workspaces.clients.destroy', $context['tenant'], [
            'client' => $context['client'],
        ]))
        ->assertRedirect(workspaceRoute('workspaces.clients.index', $context['tenant']));

    expect($context['client']->fresh()->trashed())->toBeTrue()
        ->and($context['dossier']->fresh()->trashed())->toBeTrue()
        ->and($secondDossier->fresh()->trashed())->toBeTrue();

    $this->get(workspaceRoute('workspaces.clients.index', $context['tenant']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('clients.data', 0)
            ->where('clients.total', 0));
});

test('archiving a client logs audit entries for the client and each dossier', function () {
    $context = softDeleteContext();

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->delete(workspaceRoute('workspaces.clients.destroy', $context['tenant'], [
            'client' => $context['client'],
        ]));

    expect(Activity::query()->where('event', AuditEvent::ClientDeleted->value)->exists())->toBeTrue()
        ->and(Activity::query()->where('event', AuditEvent::DossierDeleted->value)->exists())->toBeTrue();
});

test('users outside the tenant cannot archive a dossier', function () {
    $context = softDeleteContext();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->delete(workspaceRoute('workspaces.dossiers.destroy', $context['tenant'], [
            'dossier' => $context['dossier'],
        ]))
        ->assertNotFound();

    expect($context['dossier']->fresh()->trashed())->toBeFalse();
});

test('read-only members cannot archive clients', function () {
    $context = softDeleteContext();
    $reader = workspaceMember($context['tenant'], Role::ReadOnly);

    $this->actingAs($reader)
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->delete(workspaceRoute('workspaces.clients.destroy', $context['tenant'], [
            'client' => $context['client'],
        ]))
        ->assertForbidden();

    expect($context['client']->fresh()->trashed())->toBeFalse();
});

test('archived dossier subjects remain resolvable in the activity log', function () {
    $context = softDeleteContext();

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->delete(workspaceRoute('workspaces.dossiers.destroy', $context['tenant'], [
            'dossier' => $context['dossier'],
        ]));

    $activity = Activity::query()
        ->where('event', AuditEvent::DossierDeleted->value)
        ->firstOrFail();

    $this->actingAs($context['owner'])
        ->withSession(['active_tenant_id' => $context['tenant']->id])
        ->get(workspaceRoute('workspaces.activity.index', $context['tenant']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activities.data.0.id', $activity->id)
            ->where('activities.data.0.subject.name', 'Annual accounts 2025'));
});
