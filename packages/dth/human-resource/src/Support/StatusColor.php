<?php

namespace Dth\HumanResource\Support;

use Dth\HumanResource\Enums\AvailabilityStatus;
use Dth\HumanResource\Enums\BusinessFunction;
use Dth\HumanResource\Enums\EmploymentStatus;
use Dth\HumanResource\Enums\PositionAuthority;
use Dth\HumanResource\Enums\PositionGroup;

final class StatusColor
{
    public static function employment(mixed $state): string
    {
        return EmploymentStatus::tryFrom(self::value($state))?->color() ?? 'gray';
    }

    public static function availability(mixed $state): string
    {
        return AvailabilityStatus::tryFrom(self::value($state))?->color() ?? 'gray';
    }

    public static function businessFunction(mixed $state): string
    {
        return BusinessFunction::tryFrom(self::value($state))?->color() ?? 'gray';
    }

    public static function authority(mixed $state): string
    {
        return PositionAuthority::tryFrom(self::value($state))?->color() ?? 'gray';
    }

    public static function positionGroup(mixed $state): string
    {
        return PositionGroup::tryFrom(self::value($state))?->color() ?? 'gray';
    }

    private static function value(mixed $state): string
    {
        return $state instanceof \BackedEnum ? (string) $state->value : (string) $state;
    }
}
