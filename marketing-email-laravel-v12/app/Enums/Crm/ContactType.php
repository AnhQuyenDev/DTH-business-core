<?php

namespace App\Enums\Crm;

enum ContactType: string
{
    case Personal = 'personal';
    case Business = 'business';

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
            self::Personal => __('enum.contact_type.personal'),
            self::Business => __('enum.contact_type.business'),
        };
    }
}
