<?php

declare(strict_types=1);

use App\Actions\Portal\CreateClientAccessGrant;
use App\Actions\Tenancy\ProvisionTenant;
use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\UploadedDocument;
use App\Models\User;
use App\Notifications\Portal\ClientPortalInviteNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array{owner: User, tenant: mixed, client: Client, dossier: Dossier, plainTextToken: string, grant: ClientAccessGrant}
 */
function createPortalDossierWithGrant(): array
{
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $tenant->makeCurrent();

    $client = Client::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'jane@example.com',
    ]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
        'title' => '2025 Payroll dossier',
        'status' => DossierStatus::Draft,
    ]);

    $result = app(CreateClientAccessGrant::class)($dossier, $owner, 7);

    return [
        'owner' => $owner,
        'tenant' => $tenant,
        'client' => $client,
        'dossier' => $dossier->fresh(),
        'plainTextToken' => $result['plain_text_token'],
        'grant' => $result['grant'],
    ];
}

test('guest can open the client portal with a valid access token', function () {
    $context = createPortalDossierWithGrant();

    $context['tenant']->makeCurrent();
    DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'title' => 'Payslip January 2025',
    ]);

    $this->get(route('portal.show', $context['plainTextToken']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/show')
            ->where('firm.name', 'Acme Accountants')
            ->where('dossier.title', '2025 Payroll dossier')
            ->where('dossier.client.name', $context['client']->name)
            ->has('dossier.document_requests', 1));

    expect($context['grant']->fresh()->last_used_at)->not->toBeNull();
});

test('invalid or revoked portal tokens are rejected', function () {
    $context = createPortalDossierWithGrant();

    $this->get(route('portal.show', 'not-a-real-token'))
        ->assertNotFound();

    $context['tenant']->makeCurrent();
    $context['grant']->update(['revoked_at' => now()]);

    $this->get(route('portal.show', $context['plainTextToken']))
        ->assertNotFound();
});

test('guest can upload a document through the portal', function () {
    Storage::fake('local');

    $context = createPortalDossierWithGrant();

    $context['tenant']->makeCurrent();
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $context['dossier']->id,
        'title' => 'Payslip January 2025',
        'status' => DocumentRequestStatus::Pending,
    ]);

    $file = UploadedFile::fake()->create('payslip.pdf', 120, 'application/pdf');

    $this->post(
        route('portal.document-requests.upload', [
            'token' => $context['plainTextToken'],
            'documentRequest' => $documentRequest->id,
        ]),
        ['document' => $file],
    )->assertRedirect(route('portal.show', $context['plainTextToken']));

    $context['tenant']->makeCurrent();

    expect($documentRequest->fresh()->status)->toBe(DocumentRequestStatus::Submitted)
        ->and(UploadedDocument::query()->where('document_request_id', $documentRequest->id)->exists())->toBeTrue()
        ->and($context['dossier']->fresh()->status)->toBe(DossierStatus::InReview);
});

test('portal upload cannot target a document request from another dossier', function () {
    Storage::fake('local');

    $context = createPortalDossierWithGrant();

    $context['tenant']->makeCurrent();
    $otherDossier = Dossier::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'client_id' => $context['client']->id,
    ]);
    $foreignRequest = DocumentRequest::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'dossier_id' => $otherDossier->id,
        'title' => 'Foreign request',
    ]);

    $this->post(
        route('portal.document-requests.upload', [
            'token' => $context['plainTextToken'],
            'documentRequest' => $foreignRequest->id,
        ]),
        ['document' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')],
    )->assertNotFound();
});

test('staff can email a portal invite when creating an access grant', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $tenant->makeCurrent();
    $client = Client::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'jane@example.com',
    ]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('workspaces.dossiers.access-grants.store', $dossier), [
            'expires_in_days' => 7,
            'send_invite' => true,
        ])
        ->assertRedirect(route('workspaces.dossiers.show', $dossier))
        ->assertSessionHas('access_grant_token')
        ->assertSessionHas('access_grant_portal_url');

    Notification::assertSentOnDemand(ClientPortalInviteNotification::class);

    expect($dossier->fresh()->status)->toBe(DossierStatus::AwaitingClient);
});

test('staff can create a portal link without emailing', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    $tenant->makeCurrent();
    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('workspaces.dossiers.access-grants.store', $dossier), [
            'expires_in_days' => 7,
            'send_invite' => false,
        ])
        ->assertRedirect(route('workspaces.dossiers.show', $dossier));

    Notification::assertNothingSent();
});
