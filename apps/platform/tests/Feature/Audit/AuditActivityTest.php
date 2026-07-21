<?php

declare(strict_types=1);

use App\Actions\Audit\LogAuditActivityAction;
use App\Actions\Tenancy\ProvisionTenantAction;
use App\Enums\AuditEvent;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Enums\ActivityEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('viewing a dossier writes a tenant scoped audit entry', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);

    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => 'Annual accounts 2025',
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]))
        ->assertOk();

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe(AuditEvent::DossierViewed->value)
        ->and($activity->tenant_id)->toBe($tenant->id)
        ->and($activity->causer_id)->toBe($owner->id)
        ->and($activity->subject_id)->toBe($dossier->id)
        ->and($activity->getProperty('route'))->toBe('workspaces.dossiers.show');
});

test('creating a dossier logs attribute changes for the tenant', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);

    $this->actingAs($owner);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);

    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => 'Payslip archive',
        'reference' => 'PS-2025-01',
    ]);

    $activity = Activity::query()
        ->forEvent(ActivityEvent::Created)
        ->forSubject($dossier)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->tenant_id)->toBe($tenant->id)
        ->and($activity->causer_id)->toBe($owner->id)
        ->and($activity->attribute_changes?->get('attributes'))->toMatchArray([
            'title' => 'Payslip archive',
            'reference' => 'PS-2025-01',
        ]);
});

test('audit log records cannot be updated', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    $activity = app(LogAuditActivityAction::class)->handle(
        AuditEvent::DossierViewed,
        $dossier,
        causer: $owner,
    );

    expect(fn () => $activity->update(['description' => 'tampered']))
        ->toThrow(RuntimeException::class, 'Audit log records are immutable.');
});

test('audit properties discard secrets and truncate untrusted request metadata', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    request()->headers->set('User-Agent', str_repeat('x', 700));

    $activity = app(LogAuditActivityAction::class)->handle(
        AuditEvent::DossierViewed,
        $dossier,
        [
            'safe' => 'kept',
            'plain_text_token' => 'secret-token',
            'nested' => [
                'portal_url' => 'https://example.test/portal/secret-token',
                'channel' => 'mail',
            ],
        ],
        $owner,
    );

    expect($activity->getProperty('safe'))->toBe('kept')
        ->and($activity->getProperty('plain_text_token'))->toBeNull()
        ->and($activity->getProperty('nested'))->toBe(['channel' => 'mail'])
        ->and(mb_strlen((string) $activity->getProperty('user_agent')))->toBe(512)
        ->and((string) json_encode($activity->properties))->not->toContain('secret-token');
});
