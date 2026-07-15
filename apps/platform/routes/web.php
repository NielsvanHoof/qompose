<?php

declare(strict_types=1);

use App\Http\Controllers\ActiveTenantController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkspaceOnboardingController;
use App\Http\Middleware\InitializeTenantFromSession;
use App\Http\Middleware\SetPermissionTeamContext;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware([
    'auth',
    'verified',
    InitializeTenantFromSession::class,
    SetPermissionTeamContext::class,
])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('firms/{tenant:slug}/activate', [ActiveTenantController::class, 'store'])
        ->name('firms.activate');
    Route::get('onboarding/firm', [WorkspaceOnboardingController::class, 'create'])
        ->name('onboarding.firm.create');
    Route::post('onboarding/firm', [WorkspaceOnboardingController::class, 'store'])
        ->name('onboarding.firm.store');
});

require __DIR__.'/settings.php';
