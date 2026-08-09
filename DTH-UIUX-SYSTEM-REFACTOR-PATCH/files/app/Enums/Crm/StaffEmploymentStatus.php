<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum StaffEmploymentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Resigned = 'resigned';

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
            self::Active => __('enum.employment_status.active'),
            self::Inactive => __('enum.employment_status.inactive'),
            self::Resigned => __('enum.employment_status.resigned'),
        };
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Active => 'success',
            self::Inactive => 'danger',
            self::Resigned => 'gray',
        };

        return BadgePalette::status($this, $fallback);
    }
}
