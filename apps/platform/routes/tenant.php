<?php

declare(strict_types=1);

use App\Http\Controllers\DossierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Workspace\ClientAccessGrantController;
use App\Http\Controllers\Workspace\ClientController;
use App\Http\Controllers\Workspace\DocumentRequestController;
use App\Http\Middleware\EnsureValidTenantMembership;
use App\Http\Middleware\InitializeTenantFromSession;
use App\Http\Middleware\SetPermissionTeamContext;
use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;

Route::middleware([
    'web',
    'auth',
    'verified',
    InitializeTenantFromSession::class,
    EnsureValidTenantMembership::class,
    SetPermissionTeamContext::class,
    NeedsTenant::class,
    EnsureValidTenantSession::class,
])
    ->prefix('')
    ->name('workspaces.')
    ->group(function () {
        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');

        Route::get('dossiers', [DossierController::class, 'index'])->name('dossiers.index');
        Route::get('dossiers/create', [DossierController::class, 'create'])->name('dossiers.create');
        Route::post('dossiers', [DossierController::class, 'store'])->name('dossiers.store');
        Route::get('dossiers/{dossier}', [DossierController::class, 'show'])->name('dossiers.show');
        Route::post('dossiers/{dossier}/document-requests', [DocumentRequestController::class, 'store'])
            ->name('dossiers.document-requests.store');
        Route::post('dossiers/{dossier}/access-grants', [ClientAccessGrantController::class, 'store'])
            ->name('dossiers.access-grants.store');
        Route::delete('access-grants/{grant}', [ClientAccessGrantController::class, 'destroy'])
            ->name('access-grants.destroy');
    });
