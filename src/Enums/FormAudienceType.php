<?php

namespace Dth\Marketing\Enums;

use Dth\Marketing\Support\UiText;

enum FormAudienceType: string
{
    case Personal = 'personal';
    case Business = 'business';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Personal->value => UiText::get('form.audience.personal', 'Personal'),
            self::Business->value => UiText::get('form.audience.business', 'Business'),
        ];
    }

    public static function labelFor(mixed $state): string
    {
        $value = $state instanceof \BackedEnum ? (string) $state->value : (string) $state;

        return self::options()[$value] ?? UiText::status($value);
    }
}
