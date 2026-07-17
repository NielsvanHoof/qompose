<?php

declare(strict_types=1);

use App\Http\Controllers\Reporting\DashboardController;
use Illuminate\Support\Facades\Route;

// Global firm-picker dashboard — also the app home for authenticated users.
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');