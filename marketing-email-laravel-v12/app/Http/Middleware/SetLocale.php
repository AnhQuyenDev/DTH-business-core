<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('locales.supported', ['en' => 'English', 'vi' => 'Tiếng Việt']));

        $requestedLocale = $request->query('lang');
        $sessionLocale = session('locale');
        $cookieLocale = $request->cookie('locale');
        $browserLocale = $request->headers->has('Accept-Language')
            ? $request->getPreferredLanguage($supported)
            : null;

        $locale = $requestedLocale
            ?: $sessionLocale
            ?: $cookieLocale
            ?: $browserLocale
            ?: config('locales.default', config('app.locale'));

        if (! in_array($locale, $supported, true)) {
            $locale = config('locales.default', config('app.locale'));
        }

        app()->setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }
}
