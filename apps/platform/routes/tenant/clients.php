<?php

declare(strict_types=1);

use App\Http\Controllers\Clients\ClientController;
use Illuminate\Support\Facades\Route;

Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
