<?php

namespace App\Enums\Sales;

enum AudienceType: string
{
    case Personal = 'personal';
    case Business = 'business';
    case Both = 'both';

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
            self::Personal => __('enum.sales.audience_type.personal'),
            self::Business => __('enum.sales.audience_type.business'),
            self::Both => __('enum.sales.audience_type.both'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Personal => 'info',
            self::Business => 'primary',
            self::Both => 'success',
        };
    }
}
