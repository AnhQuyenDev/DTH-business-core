<?php

namespace App\Support\Localization;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LocaleManager
{
    public function supportedLocales(): array
    {
        return array_keys(config('localization.supported', ['en' => [], 'vi' => []]));
    }

    public function isSupported(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales(), true);
    }

    public function resolve(Request $request): string
    {
        $sessionLocale = $request->hasSession()
            ? $request->session()->get('locale')
            : null;

        $userLocale = $request->user()?->preferred_locale;

        foreach ([$sessionLocale, $userLocale, config('localization.default'), config('app.locale')] as $candidate) {
            if (is_string($candidate) && $this->isSupported($candidate)) {
                return $candidate;
            }
        }

        return 'en';
    }

    public function set(string $locale, Request $request, ?Authenticatable $user = null): void
    {
        if (! $this->isSupported($locale)) {
            throw new InvalidArgumentException("Unsupported locale [{$locale}].");
        }

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        $user ??= $request->user();

        if ($user && method_exists($user, 'forceFill')) {
            $user->forceFill(['preferred_locale' => $locale])->save();
        }

        app()->setLocale($locale);
    }
}
