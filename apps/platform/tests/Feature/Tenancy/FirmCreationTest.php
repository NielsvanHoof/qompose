<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenantAction;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected to the login page', function () {
    $this->get(route('firms.create'))
        ->assertRedirect(route('login'));
});

test('a user with an existing firm can view the create firm page', function () {
    $user = User::factory()->create();
    app(ProvisionTenantAction::class)->handle('Acme Accountants', $user);

    $this->actingAs($user)
        ->get(route('firms.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('firms/create'));
});

test('a user can create an additional firm and is switched to it', function () {
    $user = User::factory()->create();
    $firstTenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $user);

    $response = $this->actingAs($user)
        ->withSession(['active_tenant_id' => $firstTenant->id])
        ->post(route('firms.store'), ['name' => 'Second Firm']);

    $secondTenant = Tenant::query()->where('slug', 'second-firm')->firstOrFail();

    // Redirected into the new firm's first-client flow.
    $response->assertRedirect(
        workspaceRoute('workspaces.clients.create', $secondTenant),
    );

    // The session now points at the new firm.
    $response->assertSessionHas('active_tenant_id', $secondTenant->id);

    // The user has an active membership and the owner role in the new firm.
    $membership = TenantMembership::query()
        ->whereBelongsTo($secondTenant)
        ->whereBelongsTo($user)
        ->firstOrFail();

    expect($membership->status)->toBe(TenantMembershipStatus::Active);

    setPermissionsTeamId($secondTenant->id);
    expect($user->fresh()->hasRole(Role::Owner->value))->toBeTrue();

    // The original firm and membership are untouched.
    expect(TenantMembership::query()
        ->whereBelongsTo($firstTenant)
        ->whereBelongsTo($user)
        ->exists())->toBeTrue();
});

test('creating a firm requires a name', function () {
    $user = User::factory()->create();
    app(ProvisionTenantAction::class)->handle('Acme Accountants', $user);

    $this->actingAs($user)
        ->post(route('firms.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});
