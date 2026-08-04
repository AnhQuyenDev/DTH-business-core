<?php

namespace App\Enums\Crm;

enum InteractionStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
    case Rescheduled = 'rescheduled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled   => __('enum.interaction_status.scheduled'),
            self::Completed   => __('enum.interaction_status.completed'),
            self::Cancelled   => __('enum.interaction_status.cancelled'),
            self::NoShow      => __('enum.interaction_status.no_show'),
            self::Rescheduled => __('enum.interaction_status.rescheduled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Completed => 'success',
            self::Cancelled, self::NoShow => 'danger',
            self::Rescheduled => 'warning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
