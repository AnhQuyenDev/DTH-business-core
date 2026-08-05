<?php

namespace App\Enums\Crm;

enum CustomerAssignmentStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
    case Cancelled = 'cancelled';

    public static function values(): array
    {
        return array_map(static fn (self $v) => $v->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $v) => $v->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => __('enum.assignment_status.active'),
            self::Ended => __('enum.assignment_status.ended'),
            self::Cancelled => __('enum.assignment_status.cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Ended => 'gray',
            self::Cancelled => 'danger',
        };
    }
}
