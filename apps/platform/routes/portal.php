<?php

declare(strict_types=1);

use App\Http\Controllers\Portal\ClientPortalAnswerController;
use App\Http\Controllers\Portal\ClientPortalController;
use App\Http\Controllers\Portal\ClientPortalSessionController;
use App\Http\Controllers\Portal\ClientPortalUploadController;
use App\Http\Middleware\HardenClientPortalResponse;
use App\Http\Middleware\ResolveClientPortalGrant;
use Illuminate\Support\Facades\Route;

/* Guest client portal — magic links are exchanged for restricted sessions. */
Route::middleware([
    'web',
    'throttle:30,1',
    HardenClientPortalResponse::class,
])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('access/{token}', ClientPortalSessionController::class)
            ->where('token', '[A-Za-z0-9]+')
            ->middleware('throttle:10,1')
            ->name('exchange');

        Route::middleware(ResolveClientPortalGrant::class)->group(function (): void {
            Route::get('/', [ClientPortalController::class, 'show'])
                ->name('show');

            Route::post('document-requests/{documentRequest}/upload', [ClientPortalUploadController::class, 'store'])
                ->where('documentRequest', '[0-9]+')
                ->name('document-requests.upload');

            Route::post('document-requests/{documentRequest}/answer', [ClientPortalAnswerController::class, 'store'])
                ->where('documentRequest', '[0-9]+')
                ->name('document-requests.answer');
        });
    });
