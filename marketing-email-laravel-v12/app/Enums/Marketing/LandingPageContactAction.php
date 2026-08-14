<?php

namespace App\Enums\Marketing;

use App\Support\Ui\BadgePalette;

enum LandingPageContactAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Skipped = 'skipped';

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
            self::Created => __('enum.landing_page_contact_action.created'),
            self::Updated => __('enum.landing_page_contact_action.updated'),
            self::Skipped => __('enum.landing_page_contact_action.skipped'),
        };
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Created => 'success',
            self::Updated => 'info',
            self::Skipped => 'gray',
        };

        return BadgePalette::managed('marketing.landing_page_contact_action', $this, $fallback);
    }
}
