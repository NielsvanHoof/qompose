<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenant;
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

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('auth.user', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]));
});

test('workspace navigation is remembered as a once prop', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    app(ProvisionTenant::class)->handle('Beta Tax', $user);
    app(ProvisionTenant::class)->handle('Acme Accountants', $user);

    $this->actingAs($user)
        ->withHeaders(sharedDataInertiaHeaders())
        ->get(route('home'))
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
    app(ProvisionTenant::class)->handle('Acme Accountants', $user);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->withHeaders(sharedDataInertiaHeaders([
            Header::EXCEPT_ONCE_PROPS => 'workspaces',
        ]))
        ->get(route('home'))
        ->assertOk()
        ->assertJsonMissingPath('props.workspaces');

    $workspaceQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'tenant_memberships'));

    expect($workspaceQueries)->toBeEmpty();
});

test('workspace navigation is refreshed after provisioning a workspace', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeaders(sharedDataInertiaHeaders())
        ->get(route('home'))
        ->assertOk()
        ->assertJsonPath('props.workspaces', []);

    $this->post(route('onboarding.firm.store'), ['name' => 'Acme Accountants'])
        ->assertRedirect(workspaceRoute('workspaces.clients.create', 'acme-accountants'));

    $this->withHeaders(sharedDataInertiaHeaders([
        Header::EXCEPT_ONCE_PROPS => 'workspaces',
    ]))
        ->get(route('home'))
        ->assertOk()
        ->assertJsonPath('props.workspaces', [
            ['name' => 'Acme Accountants', 'slug' => 'acme-accountants'],
        ]);
});
