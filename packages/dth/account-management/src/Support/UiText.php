<?php
namespace Dth\AccountManagement\Support;

final class UiText
{
    public static function get(string $key, string $fallback, ?string $locale = null, string $context = 'label'): string
    {
        $locale ??= app()->getLocale();
        $translations = config('dth-account-management-translations.'.($locale ?: 'vi'), []);
        $value = data_get($translations, $key);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if ($locale !== 'vi') {
            $vi = data_get(config('dth-account-management-translations.vi', []), $key);
            if (is_string($vi) && $vi !== '') {
                return $vi;
            }
        }

        return $fallback;
    }
}
