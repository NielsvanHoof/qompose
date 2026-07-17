<?php

declare(strict_types=1);

use App\Http\Controllers\Reporting\DashboardController;
use App\Http\Controllers\Reporting\MediaLibraryController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');

Route::get('media', [MediaLibraryController::class, 'index'])->name('media.index');
