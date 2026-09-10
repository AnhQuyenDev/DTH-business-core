<?php

namespace Dth\Email\Filament\Support;

final class StatusColor
{
    public static function for(mixed $state): string
    {
        $value = $state instanceof \BackedEnum
            ? $state->value
            : (string) $state;

        return match ($value) {
            'draft', 'cancelled', 'manual', 'unsubscribe', 'unsubscribed', 'released', 'disabled' => 'gray',
            'scheduled', 'queued' => 'info',
            'processing', 'sending', 'suppressed', 'pending' => 'warning',
            'completed', 'sent', 'delivered', 'clicked', 'active', 'success', 'subscribed', 'verified' => 'success',
            'failed', 'bounced', 'complained', 'complaint', 'bounce', 'inactive', 'error' => 'danger',
            'opened' => 'primary',
            default => 'gray',
        };
    }
}
