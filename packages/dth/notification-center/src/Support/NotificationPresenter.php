<?php

namespace Dth\NotificationCenter\Support;

final class NotificationPresenter
{
    public static function typeLabel(?string $type): string
    {
        $type = $type ?: 'system';
        $labels = (array) config('dth-notification-center.types', []);
        return UiText::get('types.'.$type, (string) ($labels[$type] ?? str($type)->headline()->toString()));
    }


    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return collect(array_keys((array) config('dth-notification-center.types', [])))
            ->mapWithKeys(fn (string $type): array => [$type => self::typeLabel($type)])
            ->all();
    }

    /** @return array<string, string> */
    public static function priorityOptions(): array
    {
        return collect(array_keys((array) config('dth-notification-center.priorities', [])))
            ->mapWithKeys(fn (string $priority): array => [$priority => self::priorityLabel($priority)])
            ->all();
    }

    public static function typeTone(?string $type): string
    {
        return match ($type) {
            'action_required', 'approval' => 'amber',
            'assignment' => 'blue',
            'mention' => 'violet',
            'reminder' => 'cyan',
            'announcement' => 'indigo',
            'status_update' => 'green',
            default => 'slate',
        };
    }

    public static function icon(?string $type): string
    {
        return match ($type) {
            'action_required' => 'heroicon-o-bolt',
            'approval' => 'heroicon-o-check-badge',
            'assignment' => 'heroicon-o-user-plus',
            'mention' => 'heroicon-o-at-symbol',
            'reminder' => 'heroicon-o-clock',
            'announcement' => 'heroicon-o-megaphone',
            'status_update' => 'heroicon-o-arrow-path-rounded-square',
            default => 'heroicon-o-bell-alert',
        };
    }

    public static function priorityLabel(?string $priority): string
    {
        $priority = $priority ?: 'normal';
        $labels = (array) config('dth-notification-center.priorities', []);
        return UiText::get('priorities.'.$priority, (string) ($labels[$priority] ?? str($priority)->headline()->toString()));
    }

    public static function moduleLabel(?string $module): string
    {
        $module = $module ?: 'core';
        return UiText::get('modules.'.$module, str($module)->replace('-', ' ')->headline()->toString());
    }

    public static function channelLabel(string $channel): string
    {
        return UiText::get('channels.'.$channel, str($channel)->replace('_', ' ')->headline()->toString());
    }
}
