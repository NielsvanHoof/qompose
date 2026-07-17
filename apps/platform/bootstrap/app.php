<?php

declare(strict_types=1);

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HardenClientPortalResponse;
use App\Http\Middleware\InitializeTenantFromRoute;
use App\Http\Middleware\InitializeTenantFromSession;
use App\Http\Middleware\ResolveClientPortalGrant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require __DIR__.'/../routes/tenant.php';
            require __DIR__.'/../routes/portal.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Tenant must be current before route model binding so BelongsToTenant scopes apply.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: InitializeTenantFromSession::class,
        );

        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: InitializeTenantFromRoute::class,
        );

        // Restricted portal sessions must resolve tenant before tenant-scoped bindings.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolveClientPortalGrant::class,
        );

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(
            fn (Symfony\Component\HttpFoundation\Response $response, Throwable $exception, Request $request) => $request->is('portal/*')
                ? HardenClientPortalResponse::applyHeaders($response)
                : $response,
        );
    })->create();
