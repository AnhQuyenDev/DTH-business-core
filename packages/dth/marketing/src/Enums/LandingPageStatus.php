<?php

namespace Dth\Marketing\Enums;

use Dth\Marketing\Support\UiText;

enum LandingPageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(static fn (self $status): array => [
                $status->value => UiText::status($status),
            ])
            ->all();
    }
}
