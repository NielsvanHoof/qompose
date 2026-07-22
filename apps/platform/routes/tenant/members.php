<?php

declare(strict_types=1);

use App\Http\Controllers\Tenancy\TenantInvitationController;
use App\Http\Controllers\Tenancy\WorkspaceMemberController;
use Illuminate\Support\Facades\Route;

Route::get('members', [WorkspaceMemberController::class, 'index'])
    ->name('members.index');

Route::post('members', [WorkspaceMemberController::class, 'store'])
    ->name('members.store');

Route::patch('members/{membership}', [WorkspaceMemberController::class, 'update'])
    ->name('members.update');

Route::delete('members/{membership}', [WorkspaceMemberController::class, 'destroy'])
    ->name('members.destroy');

Route::post('members/invitations/{invitation}/resend', [TenantInvitationController::class, 'resend'])
    ->name('members.invitations.resend');

Route::delete('members/invitations/{invitation}', [TenantInvitationController::class, 'destroy'])
    ->name('members.invitations.destroy');
