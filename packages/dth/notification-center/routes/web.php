<?php

use Dth\NotificationCenter\Http\Controllers\NotificationAttachmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('notification-center')
    ->name('dth.notifications.')
    ->group(function (): void {
        Route::get('/{notification}/attachments/{attachment}', NotificationAttachmentController::class)
            ->whereUuid('notification')
            ->whereNumber('attachment')
            ->name('attachments.download');
    });
