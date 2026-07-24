<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenantAction;
use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Enums\Role;
use App\Models\Client;
use App\Models\DocumentRequest;
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
 * @return array{owner: User, tenant: mixed, dossier: Dossier}
 */
function createDossierShowContext(
    DossierStatus $status,
    bool $withDocumentRequest = true,
): array {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);

    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => 'Payroll review '.$status->value,
        'status' => $status,
    ]);

    if ($withDocumentRequest) {
        DocumentRequest::factory()->create([
            'tenant_id' => $tenant->id,
            'dossier_id' => $dossier->id,
            'title' => 'Payslip',
            'type' => 'file',
            'status' => match ($status) {
                DossierStatus::Completed, DossierStatus::InReview => DocumentRequestStatus::Accepted,
                default => DocumentRequestStatus::Pending,
            },
            'sort_order' => 0,
        ]);
    }

    return [
        'owner' => $owner,
        'tenant' => $tenant,
        'dossier' => $dossier,
    ];
}

test('dossier overview page renders props for each workflow status', function (DossierStatus $status) {
    [
        'owner' => $owner,
        'tenant' => $tenant,
        'dossier' => $dossier,
    ] = createDossierShowContext($status);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dossiers/show')
            ->where('dossier.id', $dossier->id)
            ->where('dossier.status', $status->value)
            ->has('dossier.document_requests', 1)
            ->has('dossier.review_summary')
            ->has('dossier.access_grants')
            ->has('templates')
            ->has('can_manage')
            ->has('can_edit')
            ->has('can_review')
            ->has('can_download'));
})->with([
    DossierStatus::Draft,
    DossierStatus::AwaitingClient,
    DossierStatus::InReview,
    DossierStatus::Completed,
]);

test('dossier builder page renders shared dossier props', function () {
    [
        'owner' => $owner,
        'tenant' => $tenant,
        'dossier' => $dossier,
    ] = createDossierShowContext(DossierStatus::Draft);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dossiers.builder', $tenant, ['dossier' => $dossier]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dossiers/builder')
            ->where('dossier.id', $dossier->id)
            ->has('dossier.document_requests', 1)
            ->has('templates')
            ->where('can_manage', true)
            ->has('can_edit'));
});

test('builder mutations redirect back to the builder instead of the overview', function () {
    [
        'owner' => $owner,
        'tenant' => $tenant,
        'dossier' => $dossier,
    ] = createDossierShowContext(DossierStatus::Draft);

    $builderUrl = workspaceRoute('workspaces.dossiers.builder', $tenant, ['dossier' => $dossier]);
    $existing = $dossier->documentRequests()->sole();

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(workspaceRoute('workspaces.dossiers.document-requests.store', $tenant, [
            'dossier' => $dossier,
        ]), [
            'type' => 'text',
            'title' => 'Added from builder',
        ])
        ->assertRedirect($builderUrl);

    $this->put(workspaceRoute('workspaces.dossiers.document-requests.update', $tenant, [
        'dossier' => $dossier,
        'documentRequest' => $existing,
    ]), [
        'type' => 'file',
        'title' => 'Renamed on builder',
        'instructions' => null,
    ])
        ->assertRedirect($builderUrl);

    $this->delete(workspaceRoute('workspaces.dossiers.document-requests.destroy', $tenant, [
        'dossier' => $dossier,
        'documentRequest' => $existing,
    ]))
        ->assertRedirect($builderUrl);
});

test('dossier review page renders shared dossier props', function () {
    [
        'owner' => $owner,
        'tenant' => $tenant,
        'dossier' => $dossier,
    ] = createDossierShowContext(DossierStatus::InReview);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dossiers.review', $tenant, ['dossier' => $dossier]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dossiers/review')
            ->where('dossier.id', $dossier->id)
            ->has('dossier.review_summary')
            ->where('can_review', true)
            ->has('can_download'));
});

test('empty dossier overview exposes zero form items for invite gating', function () {
    [
        'owner' => $owner,
        'tenant' => $tenant,
        'dossier' => $dossier,
    ] = createDossierShowContext(DossierStatus::Draft, withDocumentRequest: false);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dossiers.show', $tenant, ['dossier' => $dossier]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dossiers/show')
            ->has('dossier.document_requests', 0)
            ->where('dossier.review_summary.total', 0)
            ->where('dossier.ready_to_complete', false));
});

test('read-only member can view overview builder and review with restricted action flags', function () {
    [
        'tenant' => $tenant,
        'dossier' => $dossier,
    ] = createDossierShowContext(DossierStatus::AwaitingClient);

    $reader = workspaceMember($tenant, Role::ReadOnly);

    foreach (['show', 'builder', 'review'] as $action) {
        $this->actingAs($reader)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->get(workspaceRoute("workspaces.dossiers.{$action}", $tenant, ['dossier' => $dossier]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component("dossiers/{$action}")
                ->where('can_manage', false)
                ->where('can_edit', false)
                ->where('can_review', false));
    }
});

test('reviewer can view dossier surfaces with review permissions only', function () {
    [
        'tenant' => $tenant,
        'dossier' => $dossier,
    ] = createDossierShowContext(DossierStatus::InReview);

    $reviewer = workspaceMember($tenant, Role::Reviewer);

    $this->actingAs($reviewer)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(workspaceRoute('workspaces.dossiers.review', $tenant, ['dossier' => $dossier]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dossiers/review')
            ->where('can_manage', false)
            ->where('can_edit', false)
            ->where('can_review', true)
            ->has('can_download'));
});
