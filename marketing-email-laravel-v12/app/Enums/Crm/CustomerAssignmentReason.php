<?php

namespace App\Enums\Crm;

enum CustomerAssignmentReason: string
{
    case InitialDistribution = 'initial_distribution';
    case NewCustomer = 'new_customer';
    case Manual = 'manual';
    case StaffAbsence = 'staff_absence';
    case StaffReturn = 'staff_return';
    case Rebalance = 'rebalance';
    case Transfer = 'transfer';

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
            self::InitialDistribution => __('enum.assignment_reason.initial_distribution'),
            self::NewCustomer         => __('enum.assignment_reason.new_customer'),
            self::Manual              => __('enum.assignment_reason.manual'),
            self::StaffAbsence        => __('enum.assignment_reason.staff_absence'),
            self::StaffReturn         => __('enum.assignment_reason.staff_return'),
            self::Rebalance           => __('enum.assignment_reason.rebalance'),
            self::Transfer            => __('enum.assignment_reason.transfer'),
        };
    }
}
