<?php

namespace App\Http\Controllers;

use App\Support\Localization\LocaleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(string $locale, Request $request, LocaleManager $locales): RedirectResponse
    {
        abort_unless($locales->isSupported($locale), 404);

        $locales->set($locale, $request);

        return back();
    }
}
