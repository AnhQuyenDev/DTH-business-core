<?php

namespace Dth\NotificationCenter\Support;

final class UiText
{
    public static function get(string $key, string $fallback, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $translations = config('dth-notification-center-translations.'.($locale ?: 'vi'), []);
        $value = data_get($translations, $key);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if ($locale !== 'vi') {
            $vi = data_get(config('dth-notification-center-translations.vi', []), $key);
            if (is_string($vi) && $vi !== '') {
                return $vi;
            }
        }

        return $fallback;
    }
}
