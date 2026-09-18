<?php

use Dth\AccountManagement\Http\Controllers\InvitationAcceptanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'throttle:10,1'])->group(function (): void {
    Route::get('/account-invitations/accept/{token}', [InvitationAcceptanceController::class, 'show'])->name('dth.account.invitation.accept');
    Route::post('/account-invitations/accept/{token}', [InvitationAcceptanceController::class, 'accept'])->name('dth.account.invitation.accept.submit');
});
