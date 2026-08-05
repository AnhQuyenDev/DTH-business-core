<?php

namespace App\Enums\Crm;

enum CustomerConversionReason: string
{
    case ConfirmedNeed = 'confirmed_need';
    case Purchased = 'purchased';

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
            self::ConfirmedNeed => __('enum.conversion_reason.confirmed_need'),
            self::Purchased => __('enum.conversion_reason.purchased'),
        };
    }
}
