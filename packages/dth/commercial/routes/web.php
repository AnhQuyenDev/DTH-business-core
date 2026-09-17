<?php

use Dth\Commercial\Http\Controllers\CommercialReportExportController;
use Illuminate\Support\Facades\Route;

if (config('dth-commercial.enabled', true) && config('dth-commercial.features.analytics', true)) {
    Route::middleware(['web', 'auth'])->group(function (): void {
        Route::get('/commercial/reports/dashboard/{format}', CommercialReportExportController::class)
            ->whereIn('format', ['pdf', 'xlsx', 'csv'])
            ->name('dth.commercial.reports.dashboard');
    });
}
