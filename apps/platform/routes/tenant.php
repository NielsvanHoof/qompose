<?php

declare(strict_types=1);

use App\Http\Controllers\DossierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Workspace\ClientAccessGrantController;
use App\Http\Controllers\Workspace\ClientController;
use App\Http\Controllers\Workspace\DocumentRequestController;
use App\Http\Controllers\Workspace\MediaLibraryController;
use App\Http\Controllers\Workspace\QuestionnaireTemplateController;
use App\Http\Controllers\Workspace\QuestionnaireTemplateItemController;
use App\Http\Controllers\Workspace\UploadedDocumentController;
use App\Http\Middleware\EnsureValidTenantMembership;
use App\Http\Middleware\InitializeTenantFromSession;
use App\Http\Middleware\SetPermissionTeamContext;
use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;

Route::middleware([
    'web',
    'auth',
    'verified',
    InitializeTenantFromSession::class,
    EnsureValidTenantMembership::class,
    SetPermissionTeamContext::class,
    NeedsTenant::class,
    EnsureValidTenantSession::class,
])
    ->prefix('')
    ->name('workspaces.')
    ->group(function () {
        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');

        Route::get('media', [MediaLibraryController::class, 'index'])->name('media.index');

        Route::get('templates', [QuestionnaireTemplateController::class, 'index'])->name('templates.index');
        Route::get('templates/create', [QuestionnaireTemplateController::class, 'create'])->name('templates.create');
        Route::post('templates', [QuestionnaireTemplateController::class, 'store'])->name('templates.store');
        Route::get('templates/{template}', [QuestionnaireTemplateController::class, 'show'])->name('templates.show');
        Route::put('templates/{template}', [QuestionnaireTemplateController::class, 'update'])->name('templates.update');
        Route::delete('templates/{template}', [QuestionnaireTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::post('templates/{template}/copy', [QuestionnaireTemplateController::class, 'copy'])->name('templates.copy');
        Route::post('templates/{template}/items/reorder', [QuestionnaireTemplateItemController::class, 'reorder'])
            ->name('templates.items.reorder');
        Route::post('templates/{template}/items', [QuestionnaireTemplateItemController::class, 'store'])
            ->name('templates.items.store');
        Route::put('templates/{template}/items/{item}', [QuestionnaireTemplateItemController::class, 'update'])
            ->name('templates.items.update');
        Route::delete('templates/{template}/items/{item}', [QuestionnaireTemplateItemController::class, 'destroy'])
            ->name('templates.items.destroy');

        Route::get('dossiers', [DossierController::class, 'index'])->name('dossiers.index');
        Route::get('dossiers/create', [DossierController::class, 'create'])->name('dossiers.create');
        Route::post('dossiers', [DossierController::class, 'store'])->name('dossiers.store');
        Route::get('dossiers/{dossier}', [DossierController::class, 'show'])->name('dossiers.show');
        Route::post('dossiers/{dossier}/document-requests', [DocumentRequestController::class, 'store'])
            ->name('dossiers.document-requests.store');
        Route::put('dossiers/{dossier}/document-requests/{documentRequest}', [DocumentRequestController::class, 'update'])
            ->name('dossiers.document-requests.update');
        Route::delete('dossiers/{dossier}/document-requests/{documentRequest}', [DocumentRequestController::class, 'destroy'])
            ->name('dossiers.document-requests.destroy');
        Route::post('dossiers/{dossier}/document-requests/reorder', [DocumentRequestController::class, 'reorder'])
            ->name('dossiers.document-requests.reorder');
        Route::post('dossiers/{dossier}/apply-template', [DocumentRequestController::class, 'applyTemplate'])
            ->name('dossiers.apply-template');
        Route::post(
            'dossiers/{dossier}/document-requests/{documentRequest}/answer',
            [DocumentRequestController::class, 'answer'],
        )->name('dossiers.document-requests.answer');
        Route::post(
            'dossiers/{dossier}/document-requests/{documentRequest}/upload',
            [UploadedDocumentController::class, 'store'],
        )->name('dossiers.document-requests.upload');
        Route::get('uploaded-documents/{uploadedDocument}/download', [UploadedDocumentController::class, 'download'])
            ->name('uploaded-documents.download');
        Route::post('dossiers/{dossier}/access-grants', [ClientAccessGrantController::class, 'store'])
            ->name('dossiers.access-grants.store');
        Route::delete('access-grants/{grant}', [ClientAccessGrantController::class, 'destroy'])
            ->name('access-grants.destroy');
    });
