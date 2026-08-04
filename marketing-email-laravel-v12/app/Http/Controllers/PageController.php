<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function welcome()
    {
        return view('welcome');
    }

    public function languageSwitch(Request $request)
    {
        $supported = array_keys(config('locales.supported', ['en' => 'English', 'vi' => 'Tiếng Việt']));
        $current = session('locale', config('locales.default', config('app.locale')));
        $requested = $request->input('locale') ?: $request->query('locale');

        if ($requested && in_array($requested, $supported, true)) {
            $nextLocale = $requested;
        } else {
            $nextLocale = $current === 'vi' ? 'en' : 'vi';
        }

        if (! in_array($nextLocale, $supported, true)) {
            $nextLocale = config('locales.default', config('app.locale'));
        }

        session(['locale' => $nextLocale]);
        app()->setLocale($nextLocale);

        if ($request->expectsJson() || $request->isMethod('post')) {
            return response()->json(['success' => true, 'locale' => $nextLocale]);
        }

        return redirect()->back();
    }
}
