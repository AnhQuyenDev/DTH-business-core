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

    /** Like get(), but for permission keys (e.g. "accounts.view") whose dots must not be split as nested paths. */
    public static function permission(string $key, string $fallback, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $value = data_get(config('dth-account-management-translations.'.($locale ?: 'vi'), []), ['permissions', $key]);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if ($locale !== 'vi') {
            $vi = data_get(config('dth-account-management-translations.vi', []), ['permissions', $key]);
            if (is_string($vi) && $vi !== '') {
                return $vi;
            }
        }

        return $fallback;
    }

    /** Like permission(), but for the small explanatory copy shown under a permission. */
    public static function permissionDescription(string $key, string $fallback, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $value = data_get(config('dth-account-management-translations.'.($locale ?: 'vi'), []), ['permission_descriptions', $key]);

        if (is_string($value) && $value !== '') return $value;
        if ($locale !== 'vi') {
            $vi = data_get(config('dth-account-management-translations.vi', []), ['permission_descriptions', $key]);
            if (is_string($vi) && $vi !== '') return $vi;
        }
        return $fallback;
    }

}
