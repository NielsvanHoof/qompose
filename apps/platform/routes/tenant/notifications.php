<?php

declare(strict_types=1);

use App\Http\Controllers\Notifications\WorkspaceNotificationController;
use Illuminate\Support\Facades\Route;

// Mark all unread first so `{notification}` does not capture "read-all".
Route::post('notifications/read-all', [WorkspaceNotificationController::class, 'store'])
    ->name('notifications.read-all');

Route::post('notifications/{notification}/read', [WorkspaceNotificationController::class, 'update'])
    ->name('notifications.read');
