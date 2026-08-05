<?php

namespace App\Enums\Marketing;

enum ContactStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $status) => $status->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => __('enum.contact_status.active'),
            self::Inactive => __('enum.contact_status.inactive'),
            self::Archived => __('enum.contact_status.archived'),
        };
    }
}
