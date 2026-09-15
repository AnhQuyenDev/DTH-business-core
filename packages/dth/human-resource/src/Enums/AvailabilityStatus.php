<?php

namespace Dth\HumanResource\Enums;

enum AvailabilityStatus: string
{
    case Working = 'working';
    case Remote = 'remote';
    case HalfDay = 'half_day';
    case Leave = 'leave';
    case Sick = 'sick';
    case Absent = 'absent';

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Working => \Dth\HumanResource\Support\UiText::get('status.availability.working', 'Working'),
            self::Remote => \Dth\HumanResource\Support\UiText::get('status.availability.remote', 'Remote'),
            self::HalfDay => \Dth\HumanResource\Support\UiText::get('status.availability.half_day', 'Half day'),
            self::Leave => \Dth\HumanResource\Support\UiText::get('status.availability.leave', 'On leave'),
            self::Sick => \Dth\HumanResource\Support\UiText::get('status.availability.sick', 'Sick leave'),
            self::Absent => \Dth\HumanResource\Support\UiText::get('status.availability.absent', 'Absent'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Working => 'success',
            self::Remote => 'info',
            self::HalfDay => 'warning',
            self::Leave, self::Sick, self::Absent => 'danger',
        };
    }

    public function canReceiveNewWork(): bool
    {
        return in_array($this, [self::Working, self::Remote], true);
    }

    public function canSupportExistingWork(): bool
    {
        return in_array($this, [self::Working, self::Remote, self::HalfDay], true);
    }
}
