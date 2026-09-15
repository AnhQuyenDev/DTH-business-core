<?php

namespace Dth\HumanResource\Support;

final class UiText
{
    public static function get(
        string $key,
        string $default,
        array $replace = [],
        ?string $context = null,
    ): string {
        $fullKey = str_starts_with($key, 'common.') ? $key : 'hr.'.$key;

        if (function_exists('ui_t')) {
            return ui_t(
                key: $fullKey,
                default: $default,
                replace: $replace,
                module: 'human-resource',
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
}
