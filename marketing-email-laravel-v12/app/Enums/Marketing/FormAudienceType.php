<?php

namespace App\Enums\Marketing;

enum FormAudienceType: string
{
    case Personal = 'personal';
    case Business = 'business';
    case Generic = 'generic';

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
            self::Personal => __('field.form_type.personal'),
            self::Business => __('field.form_type.business'),
            self::Generic => __('field.form_type.generic'),
        };
    }
}
