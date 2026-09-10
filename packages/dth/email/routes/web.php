<?php

use Dth\Email\Http\Controllers\EmailTrackingController;
use Illuminate\Support\Facades\Route;

Route::prefix('email')->name('dth.email.')->group(function (): void {
    Route::get('/open/{token}.gif', [EmailTrackingController::class, 'open'])->name('open');
    Route::get('/click/{token}', [EmailTrackingController::class, 'click'])->name('click');
    Route::get('/unsubscribe/{token}', [EmailTrackingController::class, 'unsubscribe'])->name('unsubscribe');
    Route::post('/unsubscribe/{token}', [EmailTrackingController::class, 'unsubscribeOneClick'])->name('unsubscribe.one-click');
});

Route::middleware(['web'])
    ->prefix('email/reports')
    ->name('dth.email.reports.')
    ->group(function (): void {
        Route::get('/dashboard.pdf', [\Dth\Email\Http\Controllers\EmailReportExportController::class, 'dashboardPdf'])
            ->name('dashboard.pdf');
        Route::get('/dashboard.csv', [\Dth\Email\Http\Controllers\EmailReportExportController::class, 'dashboardCsv'])
            ->name('dashboard.csv');
        Route::get('/dashboard.xlsx', [\Dth\Email\Http\Controllers\EmailReportExportController::class, 'dashboardXlsx'])
            ->name('dashboard.xlsx');

        Route::get('/campaigns/{campaign}/report.pdf', [\Dth\Email\Http\Controllers\EmailReportExportController::class, 'campaignPdf'])
            ->name('campaign.pdf');
        Route::get('/campaigns/{campaign}/recipients.csv', [\Dth\Email\Http\Controllers\EmailReportExportController::class, 'campaignCsv'])
            ->name('campaign.csv');
        Route::get('/campaigns/{campaign}/recipients.xlsx', [\Dth\Email\Http\Controllers\EmailReportExportController::class, 'campaignXlsx'])
            ->name('campaign.xlsx');
    });
