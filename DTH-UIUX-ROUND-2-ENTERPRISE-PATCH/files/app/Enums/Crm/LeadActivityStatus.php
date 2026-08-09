<?php

namespace App\Enums\Crm;

enum LeadActivityStatus: string
{
    case Planned = 'planned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public static function values(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases(),
        );
    }

    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(
                static fn (self $case): string => $case->label(),
                self::cases(),
            ),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Planned => __('enum.lead_activity_status.planned'),
            self::Completed => __('enum.lead_activity_status.completed'),
            self::Cancelled => __('enum.lead_activity_status.cancelled'),
        };
    }

    public function color(): string
    {
        return \App\Support\Ui\BadgePalette::status($this);
    }

}
