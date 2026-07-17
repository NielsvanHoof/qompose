<?php

declare(strict_types=1);

use App\Http\Controllers\Portal\ClientAccessGrantController;
use Illuminate\Support\Facades\Route;

Route::post('dossiers/{dossier}/access-grants', [ClientAccessGrantController::class, 'store'])
    ->name('dossiers.access-grants.store');
Route::delete('access-grants/{grant}', [ClientAccessGrantController::class, 'destroy'])
    ->name('access-grants.destroy');
