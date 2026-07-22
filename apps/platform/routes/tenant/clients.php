<?php

declare(strict_types=1);

use App\Http\Controllers\Clients\ClientController;
use Illuminate\Support\Facades\Route;

Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
Route::get('clients/archived', [ClientController::class, 'archived'])->name('clients.archived');
Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
Route::patch('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
Route::patch('clients/{client}/restore', [ClientController::class, 'restore'])
    ->withTrashed()
    ->name('clients.restore');
