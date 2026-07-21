<?php

declare(strict_types=1);

use App\Http\Controllers\Production\ReadinessController;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;

Route::get('ready', ReadinessController::class)->name('ready');

Route::middleware(['web', 'auth'])
    ->get('health', HealthCheckJsonResultsController::class)
    ->name('health.details');
