<?php

namespace App\Enums\Sales;

enum PackageStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

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
            self::Active => __('enum.sales.package_status.active'),
            self::Inactive => __('enum.sales.package_status.inactive'),
            self::Archived => __('enum.sales.package_status.archived'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'danger',
            self::Archived => 'danger',
        };
    }
}
