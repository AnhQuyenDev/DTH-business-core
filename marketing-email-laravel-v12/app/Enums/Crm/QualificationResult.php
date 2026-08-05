<?php

namespace App\Enums\Crm;

enum QualificationResult: string
{
    case ConfirmedNeed = 'confirmed_need';
    case Purchased = 'purchased';
    case NoNeed = 'no_need';
    case Unreachable = 'unreachable';
    case InvalidInformation = 'invalid_information';

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
            self::ConfirmedNeed => __('enum.qualification_result.confirmed_need'),
            self::Purchased => __('enum.qualification_result.purchased'),
            self::NoNeed => __('enum.qualification_result.no_need'),
            self::Unreachable => __('enum.qualification_result.unreachable'),
            self::InvalidInformation => __('enum.qualification_result.invalid_information'),
        };
    }
}
