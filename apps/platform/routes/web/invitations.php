<?php

declare(strict_types=1);

use App\Http\Controllers\Tenancy\AcceptTenantInvitationController;
use Illuminate\Support\Facades\Route;

/*
 * Workspace member invitation accept/register — outside tenant membership
 * middleware because invitees are not active members yet.
 */
Route::get('invitations/{token}', [AcceptTenantInvitationController::class, 'show'])
    ->name('invitations.show');

Route::post('invitations/{token}/accept', [AcceptTenantInvitationController::class, 'accept'])
    ->middleware('auth')
    ->name('invitations.accept');

Route::post('invitations/{token}/register', [AcceptTenantInvitationController::class, 'register'])
    ->middleware('guest')
    ->name('invitations.register');
