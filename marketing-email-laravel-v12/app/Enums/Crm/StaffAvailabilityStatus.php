<?php

namespace App\Enums\Crm;

enum StaffAvailabilityStatus: string
{
    case Working = 'working';
    case Absent = 'absent';
    case Leave = 'leave';
    case Sick = 'sick';
    case Remote = 'remote';
    case HalfDay = 'half_day';

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
            self::Working => __('enum.availability.working'),
            self::Absent => __('enum.availability.absent'),
            self::Leave => __('enum.availability.leave'),
            self::Sick => __('enum.availability.sick'),
            self::Remote => __('enum.availability.remote'),
            self::HalfDay => __('enum.availability.half_day'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Working, self::Remote => 'success',
            self::HalfDay => 'warning',
            self::Absent, self::Leave, self::Sick => 'danger',
        };
    }

    public function canReceiveNewCustomers(): bool
    {
        return match ($this) {
            self::Working, self::Remote => true,
            self::Absent, self::Leave, self::Sick, self::HalfDay => false,
        };
    }

    public function canSupportCustomers(): bool
    {
        return match ($this) {
            self::Working, self::Remote => true,
            self::Absent, self::Leave, self::Sick => false,
            self::HalfDay => true,
        };
    }
}
