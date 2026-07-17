<?php

declare(strict_types=1);

use App\Http\Controllers\Tenancy\ActiveTenantController;
use App\Http\Controllers\Tenancy\FirmController;
use App\Http\Controllers\Tenancy\WorkspaceOnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('firms/create', [FirmController::class, 'create'])
    ->name('firms.create');
Route::post('firms', [FirmController::class, 'store'])
    ->name('firms.store');
Route::post('firms/{tenant:slug}/activate', [ActiveTenantController::class, 'store'])
    ->name('firms.activate');
Route::get('onboarding/firm', [WorkspaceOnboardingController::class, 'create'])
    ->name('onboarding.firm.create');
Route::post('onboarding/firm', [WorkspaceOnboardingController::class, 'store'])
    ->name('onboarding.firm.store');
