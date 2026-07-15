<?php

declare(strict_types=1);

use App\Http\Controllers\Portal\ClientPortalAnswerController;
use App\Http\Controllers\Portal\ClientPortalController;
use App\Http\Controllers\Portal\ClientPortalUploadController;
use App\Http\Middleware\ResolveClientPortalGrant;
use Illuminate\Support\Facades\Route;

/*
| Guest client portal — accessed via magic-link token (no login).
| ResolveClientPortalGrant makes the grant's tenant current before bindings.
*/
Route::middleware([
    'web',
    'throttle:30,1',
    ResolveClientPortalGrant::class,
])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('{token}', [ClientPortalController::class, 'show'])
            ->where('token', '[A-Za-z0-9]+')
            ->name('show');

        Route::post('{token}/document-requests/{documentRequest}/upload', [ClientPortalUploadController::class, 'store'])
            ->where(['token' => '[A-Za-z0-9]+', 'documentRequest' => '[0-9]+'])
            ->name('document-requests.upload');

        Route::post('{token}/document-requests/{documentRequest}/answer', [ClientPortalAnswerController::class, 'store'])
            ->where(['token' => '[A-Za-z0-9]+', 'documentRequest' => '[0-9]+'])
            ->name('document-requests.answer');
    });
