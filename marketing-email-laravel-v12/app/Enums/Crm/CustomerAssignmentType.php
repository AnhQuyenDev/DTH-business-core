<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum CustomerAssignmentType: string
{
    case Owner = 'owner';
    case Support = 'support';

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
            self::Owner => __('enum.assignment_type.owner'),
            self::Support => __('enum.assignment_type.support'),
        };
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Owner => 'success',
            self::Support => 'info',
        };

        return BadgePalette::managed('crm.customer_assignment_type', $this, $fallback);
    }
}
