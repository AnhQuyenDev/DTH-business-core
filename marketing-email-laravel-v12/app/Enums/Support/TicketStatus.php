<?php

namespace App\Enums\Support;

use App\Support\Ui\BadgePalette;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case PendingCustomer = 'pending_customer';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return __('v1.support.status.'.$this->value);
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Open => 'warning',
            self::InProgress => 'info',
            self::PendingCustomer => 'gray',
            self::Resolved => 'success',
            self::Closed => 'gray',
        };

        return BadgePalette::managed('support.ticket_status', $this, $fallback);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [
            $status->value => $status->label(),
        ])->all();
    }
}
