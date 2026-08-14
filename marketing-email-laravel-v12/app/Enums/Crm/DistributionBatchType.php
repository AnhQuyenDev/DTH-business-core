<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum DistributionBatchType: string
{
    case Initial = 'initial';
    case NewCustomer = 'new_customer';
    case StaffAbsence = 'staff_absence';
    case StaffReturn = 'staff_return';
    case Rebalance = 'rebalance';
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
            self::Initial => __('enum.distribution_batch_type.initial'),
            self::NewCustomer => __('enum.distribution_batch_type.new_customer'),
            self::StaffAbsence => __('enum.distribution_batch_type.staff_absence'),
            self::StaffReturn => __('enum.distribution_batch_type.staff_return'),
            self::Rebalance => __('enum.distribution_batch_type.rebalance'),
            self::Manual => __('enum.distribution_batch_type.manual'),
        };
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Initial, self::NewCustomer, self::StaffReturn => 'info',
            self::StaffAbsence, self::Rebalance, self::Manual => 'warning',
        };

        return BadgePalette::managed('crm.distribution_batch_type', $this, $fallback);
    }
}
