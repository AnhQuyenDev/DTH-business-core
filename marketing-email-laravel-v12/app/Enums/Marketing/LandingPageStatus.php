<?php

namespace App\Enums\Marketing;

enum LandingPageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $s) => $s->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('enum.landing_page_status.draft'),
            self::Published => __('enum.landing_page_status.published'),
            self::Archived => __('enum.landing_page_status.archived'),
        };
    }
}
