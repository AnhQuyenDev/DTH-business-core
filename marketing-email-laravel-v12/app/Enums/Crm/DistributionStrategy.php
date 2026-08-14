<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum DistributionStrategy: string
{
    case RoundRobin = 'round_robin';
    case LeastLoaded = 'least_loaded';
    case Weighted = 'weighted';
    case Manual = 'manual';

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
            self::RoundRobin => __('enum.distribution_strategy.round_robin'),
            self::LeastLoaded => __('enum.distribution_strategy.least_loaded'),
            self::Weighted => __('enum.distribution_strategy.weighted'),
            self::Manual => __('enum.distribution_strategy.manual'),
        };
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::RoundRobin, self::LeastLoaded, self::Weighted => 'info',
            self::Manual => 'warning',
        };

        return BadgePalette::managed('crm.distribution_strategy', $this, $fallback);
    }
}
