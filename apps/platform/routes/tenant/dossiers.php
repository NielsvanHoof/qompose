<?php

declare(strict_types=1);

use App\Http\Controllers\Dossiers\DocumentRequestController;
use App\Http\Controllers\Dossiers\DossierCompletionController;
use App\Http\Controllers\Dossiers\DossierController;
use Illuminate\Support\Facades\Route;

Route::get('dossiers', [DossierController::class, 'index'])->name('dossiers.index');
Route::get('dossiers/create', [DossierController::class, 'create'])->name('dossiers.create');
Route::post('dossiers', [DossierController::class, 'store'])->name('dossiers.store');
Route::get('dossiers/{dossier}', [DossierController::class, 'show'])->name('dossiers.show');
Route::post('dossiers/{dossier}/complete', [DossierCompletionController::class, 'store'])
    ->name('dossiers.complete');
Route::post('dossiers/{dossier}/apply-template', [DocumentRequestController::class, 'applyTemplate'])
    ->name('dossiers.apply-template');
