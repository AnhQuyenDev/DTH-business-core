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
            'draft', 'cancelled', 'archived', 'skipped', 'unknown', 'not_available' => 'gray',
            'active', 'published', 'processed', 'created', 'completed', 'success', 'healthy' => 'success',
            'paused', 'pending', 'received' => 'warning',
            'updated', 'info' => 'info',
            'failed', 'spam', 'error', 'critical', 'unhealthy' => 'danger',
            default => 'gray',
        };
    }
}
