<?php

namespace Dth\Marketing\Filament\Support;

final class StatusColor
{
    public static function for(mixed $state): string
    {
        $value = $state instanceof \BackedEnum
            ? $state->value
            : (string) $state;

        return match ($value) {
            'personal' => 'info',
            'business' => 'success',
            'subscribed', 'created', 'active', 'published', 'processed', 'completed', 'success', 'healthy' => 'success',
            'unsubscribed', 'failed', 'spam', 'error', 'critical', 'unhealthy' => 'danger',
            'draft', 'cancelled', 'archived', 'skipped', 'unknown', 'not_available' => 'gray',
            'paused', 'pending', 'received' => 'warning',
            'updated', 'info' => 'info',
            default => 'gray',
        };
    }
}
