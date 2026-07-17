<?php

declare(strict_types=1);

use App\Http\Controllers\Reporting\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
