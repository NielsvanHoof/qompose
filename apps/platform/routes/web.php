<?php

declare(strict_types=1);

use App\Http\Middleware\InitializeTenantFromSession;
use App\Http\Middleware\SetPermissionTeamContext;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

/*
 * Authenticated (non-workspace) routes, split per domain under routes/web/.
 * These resolve the tenant from the session instead of the URL.
 */
Route::middleware([
    'auth',
    'verified',
    InitializeTenantFromSession::class,
    SetPermissionTeamContext::class,
])->group(function () {
    require __DIR__.'/web/reporting.php';
    require __DIR__.'/web/tenancy.php';
});

require __DIR__.'/settings.php';
