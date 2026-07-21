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
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

// Policy and Query unit tests need the Laravel app + database.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit/Policies', 'Unit/Queries');

pest()->presets()->custom('qompose', function () {
    return [
        expect('App')
            ->toUseStrictTypes(),

        expect('App\Actions')
            ->classes()
            ->toBeFinal(),

        // Domain actions end in Action; Fortify Accounts contracts keep framework names.
        expect('App\Actions')
            ->classes()
            ->toHaveSuffix('Action')
            ->ignoring([
                'App\Actions\Accounts',
            ]),

        expect('App\Actions')
            ->classes()
            ->toHaveMethod('handle')
            ->ignoring([
                'App\Actions\Accounts',
                'App\Actions\Audit\LogTenantActivityAction',
            ]),

        expect('App\Actions')
            ->classes()
            ->not->toHaveMethod('__invoke')
            ->ignoring([
                'App\Actions\Accounts',
                'App\Actions\Audit\LogTenantActivityAction',
            ]),

        // Read models end in Query; Spatie filters and row mappers are not Queries.
        expect('App\Queries')
            ->classes()
            ->toHaveSuffix('Query')
            ->ignoring([
                'App\Queries\Filters',
                'App\Queries\Reporting\ActivityLogRowMapper',
            ]),

        expect('App\Queries')
            ->classes()
            ->toHaveMethod('handle')
            ->ignoring([
                'App\Queries\PaginatedIndexQuery',
                'App\Queries\Filters',
                'App\Queries\Reporting\ActivityLogRowMapper',
            ]),

        expect('App\Queries')
            ->classes()
            ->not->toHaveMethod('__invoke')
            ->ignoring([
                'App\Queries\PaginatedIndexQuery',
                'App\Queries\Filters',
                'App\Queries\Reporting\ActivityLogRowMapper',
            ]),

        expect('App\Queries')
            ->classes()
            ->toBeFinal()
            ->ignoring([
                'App\Queries\PaginatedIndexQuery',
            ]),

        expect('App\Http\Controllers\Controller')
            ->toBeAbstract(),

        expect('App\Http\Controllers')
            ->classes()
            ->toExtend('App\Http\Controllers\Controller')
            ->toHaveSuffix('Controller')
            ->not->toHavePublicMethodsBesides([
                '__construct',
                '__invoke',
                'index',
                'show',
                'create',
                'store',
                'edit',
                'update',
                'destroy',
                'middleware',
                // Domain-specific controller actions beyond REST defaults.
                'copy',
                'reorder',
                'applyTemplate',
                'answer',
                'download',
            ]),

        expect('App\Models\Activity')
            ->toExtend('Spatie\Activitylog\Models\Activity'),
    ];
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Shared helpers for Feature and Unit tests. Prefer these over repeating
| ProvisionTenantAction + membership + role setup in every file.
|
*/

/**
 * @param  array<string, mixed>  $parameters
 */
function workspaceRoute(
    string $name,
    Tenant|string $tenant,
    array $parameters = [],
): string {
    return route($name, ['tenant' => $tenant, ...$parameters]);
}

/**
 * Seed roles/permissions, provision a firm, and activate its tenant context.
 *
 * @return array{owner: User, tenant: Tenant}
 */
function provisionWorkspace(string $name = 'Acme Accountants', ?User $owner = null): array
{
    test()->seed(RolesAndPermissionsSeeder::class);

    $owner ??= User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle($name, $owner);

    actingInWorkspace($tenant);

    return [
        'owner' => $owner->fresh(),
        'tenant' => $tenant,
    ];
}

/**
 * Set the current Spatie tenant + permission team for the request lifecycle.
 */
function actingInWorkspace(Tenant $tenant): void
{
    $tenant->makeCurrent();
    setPermissionsTeamId($tenant->id);
}

/**
 * Attach an active membership and assign a tenant-scoped role.
 */
function workspaceMember(Tenant $tenant, Role $role, ?User $user = null): User
{
    $user ??= User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    actingInWorkspace($tenant);
    $user->assignRole($role->value);

    return $user->fresh();
}
