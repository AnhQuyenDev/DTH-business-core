<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum CustomerStatus: string
{
    case Potential = 'potential';
    case Active = 'active';
    case Inactive = 'inactive';
    case Churned = 'churned';
    case Blocked = 'blocked';
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
            self::Potential => __('enum.status.potential'),
            self::Active => __('enum.status.active'),
            self::Inactive => __('enum.status.inactive'),
            self::Churned => __('enum.customer_status.churned'),
            self::Blocked => __('enum.status.blocked'),
            self::Archived => __('enum.status.archived'),
        };
    }

    public function isEmailMarketable(): bool
    {
        return in_array($this, [self::Potential, self::Active], true);
    }

    public function color(): string
    {
        return BadgePalette::status($this);
    }

}
