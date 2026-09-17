<?php

namespace Dth\Commercial\Support;

final class StatusColor
{
    public static function for(mixed $state): string
    {
        $value = $state instanceof \BackedEnum ? $state->value : (string) $state;

        return match ($value) {
            // Shared DTH semantic statuses: keep these aligned with CRM,
            // Marketing and Email so the same state always reads the same.
            'active', 'qualified', 'won' => 'success',
            'inactive', 'archived', 'cancelled' => 'gray',
            'lost' => 'danger',

            // Commercial-only opportunity stages.
            'discovery' => 'info',
            'proposal' => 'warning',
            'negotiation' => 'primary',

            default => 'gray',
        };
    }
}
