<?php

declare(strict_types=1);

use App\Http\Controllers\Dossiers\DocumentRequestController;
use App\Http\Controllers\Dossiers\DocumentRequestReviewController;
use App\Http\Controllers\Dossiers\UploadedDocumentController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

/*
 * Nested dossier → document-request routes use scoped bindings so a
 * document request ID from another dossier returns 404 automatically.
 */
Route::scopeBindings()->group(function (): void {
    Route::post('dossiers/{dossier}/document-requests', [DocumentRequestController::class, 'store'])
        ->name('dossiers.document-requests.store');
    Route::put('dossiers/{dossier}/document-requests/{documentRequest}', [DocumentRequestController::class, 'update'])
        ->name('dossiers.document-requests.update');
    Route::delete('dossiers/{dossier}/document-requests/{documentRequest}', [DocumentRequestController::class, 'destroy'])
        ->name('dossiers.document-requests.destroy');
    Route::post('dossiers/{dossier}/document-requests/reorder', [DocumentRequestController::class, 'reorder'])
        ->name('dossiers.document-requests.reorder');
    Route::post(
        'dossiers/{dossier}/document-requests/{documentRequest}/answer',
        [DocumentRequestReviewController::class, 'answer'],
    )->name('dossiers.document-requests.answer');
    Route::post(
        'dossiers/{dossier}/document-requests/{documentRequest}/upload',
        [UploadedDocumentController::class, 'store'],
    )->name('dossiers.document-requests.upload');
    Route::post(
        'dossiers/{dossier}/document-requests/{documentRequest}/review',
        [DocumentRequestReviewController::class, 'store'],
    )->name('dossiers.document-requests.review');
});

// Not nested under a dossier — BelongsToTenant global scope is enough.
Route::get('uploaded-documents/{uploadedDocument}', [UploadedDocumentController::class, 'show'])
    ->middleware(RequirePassword::using(
        passwordTimeoutSeconds: (int) config('auth.document_access_timeout', 900),
    ))
    ->name('uploaded-documents.show');
Route::get('uploaded-documents/{uploadedDocument}/download', [UploadedDocumentController::class, 'download'])
    ->middleware(RequirePassword::using(
        passwordTimeoutSeconds: (int) config('auth.document_access_timeout', 900),
    ))
    ->name('uploaded-documents.download');
