<?php

namespace App\Enums\Sales;

enum BillingPeriodUnit: string
{
    case Day = 'day';
    case Month = 'month';
    case Year = 'year';
    case OneTime = 'one_time';

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
            self::Day => __('enum.sales.billing_period_unit.day'),
            self::Month => __('enum.sales.billing_period_unit.month'),
            self::Year => __('enum.sales.billing_period_unit.year'),
            self::OneTime => __('enum.sales.billing_period_unit.one_time'),
        };
    }
}
