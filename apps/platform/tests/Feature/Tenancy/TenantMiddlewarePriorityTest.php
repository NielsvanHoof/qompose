<?php

declare(strict_types=1);

use App\Http\Middleware\InitializeTenantFromRoute;
use App\Http\Middleware\InitializeTenantFromSession;
use App\Http\Middleware\ResolveClientPortalGrant;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Middleware\SubstituteBindings;

test('tenant initialization middleware runs before route model binding', function () {
    /** @var Kernel $kernel */
    $kernel = app(Kernel::class);

    $priorityProperty = new ReflectionProperty(Kernel::class, 'middlewarePriority');
    $priorityProperty->setAccessible(true);

    /** @var list<class-string> $priority */
    $priority = $priorityProperty->getValue($kernel);

    $substituteBindingsIndex = array_search(SubstituteBindings::class, $priority, true);

    expect($substituteBindingsIndex)->not->toBeFalse();

    $tenantMiddleware = [
        InitializeTenantFromSession::class,
        InitializeTenantFromRoute::class,
        ResolveClientPortalGrant::class,
    ];

    foreach ($tenantMiddleware as $middleware) {
        $index = array_search($middleware, $priority, true);

        expect($index)->not->toBeFalse()
            ->and($index)->toBeLessThan($substituteBindingsIndex);
    }

    expect(array_search(InitializeTenantFromSession::class, $priority, true))
        ->toBeLessThan(array_search(InitializeTenantFromRoute::class, $priority, true))
        ->and(array_search(InitializeTenantFromRoute::class, $priority, true))
        ->toBeLessThan(array_search(ResolveClientPortalGrant::class, $priority, true));
});
