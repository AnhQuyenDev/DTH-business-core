<?php

use App\Support\Localization\LocaleManager;
use Dth\Marketing\Http\Controllers\FormTemplatePreviewController;
use Dth\Marketing\Http\Controllers\LandingPageController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::post('/locale/{locale}', function (string $locale, LocaleManager $locales): RedirectResponse {
        $locales->set($locale, request());

        return back();
    })->name('locale.switch');
});

if (config('dth-marketing.enabled', true)) {
    Route::middleware('web')->group(function (): void {
        Route::get('/lp/{slug}', [LandingPageController::class, 'show'])
            ->name('marketing.landing-pages.public.show');

        Route::middleware('auth')->group(function (): void {
            Route::get(
                '/marketing/form-templates/{formTemplate}/preview',
                FormTemplatePreviewController::class,
            )->name('marketing.form-templates.preview');

            Route::get(
                '/marketing/landing-pages/{landingPage}/preview',
                [LandingPageController::class, 'preview'],
            )->name('marketing.landing-pages.preview');
        });
    });
}
