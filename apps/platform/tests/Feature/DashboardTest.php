<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shares active workspaces for sidebar navigation', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => 'Acme Accountants',
        'slug' => 'acme-accountants',
    ]);
    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('workspaces', [
                ['name' => 'Acme Accountants', 'slug' => 'acme-accountants'],
            ]));
});

test('verified users can create a workspace from the dashboard', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('workspaces.store'), ['name' => 'Acme Accountants'])
        ->assertRedirect(route('workspaces.dossiers.index', 'acme-accountants'));

    $tenant = Tenant::query()->sole();
    $membership = TenantMembership::query()->sole();

    setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles');

    expect($tenant->slug)->toBe('acme-accountants')
        ->and($membership->tenant_id)->toBe($tenant->id)
        ->and($membership->user_id)->toBe($user->id)
        ->and($membership->isActive())->toBeTrue()
        ->and($user->hasRole(Role::Owner->value))->toBeTrue();
});

test('workspace onboarding requires a name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('workspaces.store'), ['name' => ''])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('name');
});

test('unverified users are redirected to email verification', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('verification.notice'));
});

test('guests cannot create workspaces', function () {
    $this->post(route('workspaces.store'), ['name' => 'Acme Accountants'])
        ->assertRedirect(route('login'));
});
