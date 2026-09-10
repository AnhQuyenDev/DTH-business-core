<?php

namespace Dth\Email\Support;

final class UiText
{
    public static function get(
        string $key,
        string $default,
        array $replace = [],
        ?string $context = null,
    ): string {
        $fullKey = str_starts_with($key, 'common.')
            ? $key
            : 'email.'.$key;

        if (function_exists('ui_t')) {
            return ui_t(
                key: $fullKey,
                default: $default,
                replace: $replace,
                module: 'email',
                context: $context,
            );
        }

        if ($replace === []) {
            return $default;
        }

        $pairs = [];

        foreach ($replace as $name => $value) {
            $pairs[':'.$name] = (string) $value;
        }

        return strtr($default, $pairs);
    }

    public static function status(mixed $state): string
    {
        if (function_exists('ui_status')) {
            return ui_status($state);
        }

        $value = $state instanceof \BackedEnum
            ? $state->value
            : (string) $state;

        return ucfirst(str_replace(['_', '-'], ' ', $value));
    }
}
