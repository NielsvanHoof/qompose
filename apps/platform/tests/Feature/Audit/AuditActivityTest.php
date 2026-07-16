<?php

declare(strict_types=1);

use App\Actions\Audit\LogAuditActivity;
use App\Actions\Tenancy\ProvisionTenant;
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
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

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
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

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
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    $activity = app(LogAuditActivity::class)(
        AuditEvent::DossierViewed,
        $dossier,
        causer: $owner,
    );

    expect(fn () => $activity->update(['description' => 'tampered']))
        ->toThrow(RuntimeException::class, 'Audit log records are immutable.');
});
