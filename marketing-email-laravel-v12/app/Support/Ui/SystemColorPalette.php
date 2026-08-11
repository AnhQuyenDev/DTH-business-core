<?php

namespace App\Support\Ui;

/**
 * Single source of truth for user-selectable colors used by configuration UI.
 *
 * Filament's semantic colors remain available, while additional registered
 * palettes provide enough visual choices for configurable identities such as departments, roles, functions, and audiences. Business status colors stay semantic and locked.
 */
final class SystemColorPalette
{
    public const DEFAULT = 'gray';

    public const COLORS = [
        'gray',
        'primary',
        'info',
        'success',
        'warning',
        'danger',
        'orange',
        'amber',
        'yellow',
        'lime',
        'green',
        'emerald',
        'teal',
        'cyan',
        'sky',
        'blue',
        'indigo',
        'violet',
        'purple',
        'fuchsia',
        'pink',
        'rose',
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return self::COLORS;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::COLORS)
            ->mapWithKeys(fn (string $color): array => [
                $color => __('configuration.colors.'.$color),
            ])
            ->all();
    }

    /** @return array<string, string> */
    public static function toggleColors(): array
    {
        return collect(self::COLORS)
            ->mapWithKeys(fn (string $color): array => [$color => $color])
            ->all();
    }

    public static function isSupported(?string $color): bool
    {
        return is_string($color) && in_array($color, self::COLORS, true);
    }

    public static function normalize(?string $color, string $fallback = self::DEFAULT): string
    {
        if (self::isSupported($color)) {
            return $color;
        }

        return self::isSupported($fallback) ? $fallback : self::DEFAULT;
    }
}
