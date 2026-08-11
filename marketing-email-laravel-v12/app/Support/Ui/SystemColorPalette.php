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


    /** @return array<string, string> */
    public static function hexMap(): array
    {
        return [
            'gray' => '#6b7280',
            'primary' => '#f59e0b',
            'info' => '#0ea5e9',
            'success' => '#22c55e',
            'warning' => '#f59e0b',
            'danger' => '#ef4444',
            'orange' => '#f97316',
            'amber' => '#f59e0b',
            'yellow' => '#eab308',
            'lime' => '#84cc16',
            'green' => '#22c55e',
            'emerald' => '#10b981',
            'teal' => '#14b8a6',
            'cyan' => '#06b6d4',
            'sky' => '#0ea5e9',
            'blue' => '#3b82f6',
            'indigo' => '#6366f1',
            'violet' => '#8b5cf6',
            'purple' => '#a855f7',
            'fuchsia' => '#d946ef',
            'pink' => '#ec4899',
            'rose' => '#f43f5e',
        ];
    }

    public static function hex(?string $color): string
    {
        $key = static::normalize($color);

        return static::hexMap()[$key] ?? static::hexMap()[self::DEFAULT];
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
