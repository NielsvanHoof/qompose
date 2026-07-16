<?php

declare(strict_types=1);

use App\Models\Tenant;
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

pest()->presets()->custom('qompose', function () {
    return [
        expect('App')
            ->toUseStrictTypes(),

        expect('App\Actions')
            ->classes()
            ->toBeFinal()
            ->toBeInvokable()
            ->ignoring([
                'App\Actions\Accounts',
                'App\Actions\Audit\LogTenantActivityAction',
            ]),

        expect('App\Queries')
            ->classes()
            ->toBeFinal()
            ->toBeInvokable(),

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
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
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
