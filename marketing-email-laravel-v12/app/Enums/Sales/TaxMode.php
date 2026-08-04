<?php

namespace App\Enums\Sales;

enum TaxMode: string
{
    case Inclusive = 'inclusive';
    case Exclusive = 'exclusive';
    case None = 'none';

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
            self::Inclusive => __('enum.sales.tax_mode.inclusive'),
            self::Exclusive => __('enum.sales.tax_mode.exclusive'),
            self::None => __('enum.sales.tax_mode.none'),
        };
    }
}
