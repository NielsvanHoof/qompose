<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenantAction;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Support\Header;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * @param  array<string, string>  $additionalHeaders
 * @return array<string, string>
 */
function sharedDataInertiaHeaders(array $additionalHeaders = []): array
{
    return [
        Header::INERTIA => 'true',
        Header::VERSION => app(HandleInertiaRequests::class)->version(request()) ?? '',
        ...$additionalHeaders,
    ];
}

test('shared authenticated user data is explicitly projected', function () {
    $user = User::factory()->create();

    // Profile is always reachable for verified users (unlike `/`, which may redirect).
    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/profile')
            ->where('auth.user', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->getAttributes()['locale'] ?? null,
            ]));
});

test('workspace navigation is remembered as a once prop', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    app(ProvisionTenantAction::class)->handle('Beta Tax', $user);
    app(ProvisionTenantAction::class)->handle('Acme Accountants', $user);

    $this->actingAs($user)
        ->withHeaders(sharedDataInertiaHeaders())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertJsonPath('props.workspaces', [
            ['name' => 'Acme Accountants', 'slug' => 'acme-accountants'],
            ['name' => 'Beta Tax', 'slug' => 'beta-tax'],
        ])
        ->assertJsonPath('onceProps.workspaces.prop', 'workspaces');
});

test('remembered workspace navigation is not queried on every inertia response', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    // Two firms so session middleware does not auto-select a tenant (and query further).
    app(ProvisionTenantAction::class)->handle('Beta Tax', $user);
    app(ProvisionTenantAction::class)->handle('Acme Accountants', $user);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->withHeaders(sharedDataInertiaHeaders([
            Header::EXCEPT_ONCE_PROPS => 'workspaces',
        ]))
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertJsonMissingPath('props.workspaces');

    // Navigation loads tenants (name/slug); session middleware only touches memberships.
    $workspaceNavigationQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'from `tenants`')
            || str_contains($query['query'], 'from "tenants"'));

    expect($workspaceNavigationQueries)->toBeEmpty();
});

test('workspace navigation is refreshed after provisioning a workspace', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeaders(sharedDataInertiaHeaders())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertJsonPath('props.workspaces', []);

    $this->post(route('onboarding.firm.store'), ['name' => 'Acme Accountants'])
        ->assertRedirect(workspaceRoute('workspaces.clients.create', 'acme-accountants'));

    $this->withHeaders(sharedDataInertiaHeaders([
        Header::EXCEPT_ONCE_PROPS => 'workspaces',
    ]))
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertJsonPath('props.workspaces', [
            ['name' => 'Acme Accountants', 'slug' => 'acme-accountants'],
        ]);
});
