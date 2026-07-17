<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureValidTenantMembership;
use App\Http\Middleware\InitializeTenantFromRoute;
use App\Http\Middleware\SetPermissionTeamContext;
use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;

/*
 * Staff workspace routes, split per domain under routes/tenant/.
 * The middleware stack, prefix, and name prefix are applied once here
 * so every domain file only declares its own routes.
 */
Route::middleware([
    'web',
    'auth',
    'verified',
    InitializeTenantFromRoute::class,
    EnsureValidTenantMembership::class,
    SetPermissionTeamContext::class,
    NeedsTenant::class,
])
    ->prefix('workspaces/{tenant:slug}')
    ->name('workspaces.')
    ->group(function () {
        require __DIR__.'/tenant/reporting.php';
        require __DIR__.'/tenant/clients.php';
        require __DIR__.'/tenant/questionnaires.php';
        require __DIR__.'/tenant/dossiers.php';
        require __DIR__.'/tenant/document-requests.php';
        require __DIR__.'/tenant/access-grants.php';
    });
