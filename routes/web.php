<?php

use App\Support\Localization\LocaleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::post('/locale/{locale}', function (string $locale, LocaleManager $locales): RedirectResponse {
        $locales->set($locale, request());

        return back();
    })->name('locale.switch');
});
