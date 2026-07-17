<?php

declare(strict_types=1);

use App\Http\Controllers\Dossiers\DocumentRequestController;
use App\Http\Controllers\Dossiers\DocumentRequestReviewController;
use App\Http\Controllers\Dossiers\UploadedDocumentController;
use Illuminate\Support\Facades\Route;

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
    [DocumentRequestController::class, 'answer'],
)->name('dossiers.document-requests.answer');
Route::post(
    'dossiers/{dossier}/document-requests/{documentRequest}/upload',
    [UploadedDocumentController::class, 'store'],
)->name('dossiers.document-requests.upload');
Route::post(
    'dossiers/{dossier}/document-requests/{documentRequest}/review',
    [DocumentRequestReviewController::class, 'store'],
)->name('dossiers.document-requests.review');
Route::get('uploaded-documents/{uploadedDocument}/download', [UploadedDocumentController::class, 'download'])
    ->name('uploaded-documents.download');
