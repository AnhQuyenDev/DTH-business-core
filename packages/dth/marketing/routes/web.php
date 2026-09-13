<?php

use Dth\Marketing\Http\Controllers\FormTemplatePreviewController;
use Dth\Marketing\Http\Controllers\LandingPageController;
use Dth\Marketing\Http\Controllers\MarketingReportExportController;
use Illuminate\Support\Facades\Route;

if (config('dth-marketing.enabled', true)) {
    Route::middleware('web')->group(function (): void {
        Route::get('/lp/{slug}', [LandingPageController::class, 'show'])
            ->name('marketing.landing-pages.public.show');

        if (config('dth-marketing.features.public_submission', false)) {
            Route::post('/lp/{slug}/submit', [LandingPageController::class, 'submit'])
                ->middleware('throttle:dth-marketing-submissions')
                ->name('marketing.landing-pages.public.submit');
            Route::get('/lp/{slug}/thank-you', [LandingPageController::class, 'thankYou'])
                ->name('marketing.landing-pages.public.thank-you');
        }

        Route::middleware('auth')->group(function (): void {
            Route::get(
                '/marketing/form-templates/{formTemplate}/preview',
                FormTemplatePreviewController::class,
            )->name('marketing.form-templates.preview');

            Route::get(
                '/marketing/landing-pages/{landingPage}/preview',
                [LandingPageController::class, 'preview'],
            )->name('marketing.landing-pages.preview');

            if (config('dth-marketing.features.analytics', false)) {
                Route::get(
                    '/marketing/reports/dashboard/{format}',
                    [MarketingReportExportController::class, 'dashboard'],
                )->whereIn('format', ['pdf', 'xlsx', 'csv'])
                    ->name('dth.marketing.reports.dashboard');

                Route::get(
                    '/marketing/reports/campaigns/{campaign}/{format}',
                    [MarketingReportExportController::class, 'campaign'],
                )->whereIn('format', ['pdf', 'xlsx', 'csv'])
                    ->name('dth.marketing.reports.campaign');
            }
        });
    });
}
