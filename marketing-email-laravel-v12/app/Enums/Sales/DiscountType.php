<?php

namespace App\Enums\Sales;

enum DiscountType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';

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
            self::Fixed => __('enum.sales.discount_type.fixed'),
            self::Percentage => __('enum.sales.discount_type.percentage'),
        };
    }
}
