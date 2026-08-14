<?php

namespace App\Enums\Support;

use App\Support\Ui\BadgePalette;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return __('v1.support.priority.'.$this->value);
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Low => 'gray',
            self::Normal => 'info',
            self::High => 'warning',
            self::Urgent => 'danger',
        };

        return BadgePalette::managed('support.ticket_priority', $this, $fallback);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $priority) => [
            $priority->value => $priority->label(),
        ])->all();
    }
}
