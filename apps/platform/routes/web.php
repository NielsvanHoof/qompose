<?php

declare(strict_types=1);

use App\Http\Controllers\WorkspaceOnboardingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::post('workspaces', [WorkspaceOnboardingController::class, 'store'])
        ->name('workspaces.store');
});

require __DIR__.'/settings.php';
