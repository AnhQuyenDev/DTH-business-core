<?php

use Dth\Email\Http\Controllers\EmailTrackingController;
use Illuminate\Support\Facades\Route;

Route::prefix('email')->name('dth.email.')->group(function (): void {
    Route::get('/open/{token}.gif', [EmailTrackingController::class, 'open'])->name('open');
    Route::get('/click/{token}', [EmailTrackingController::class, 'click'])->name('click');
    Route::get('/unsubscribe/{token}', [EmailTrackingController::class, 'unsubscribe'])->name('unsubscribe');
});
