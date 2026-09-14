<?php

namespace Dth\Crm\Enums;

use Dth\Crm\Support\CrmOptions;

enum StaffAvailabilityStatus: string
{
    case Working = 'working';
    case Absent = 'absent';
    case Leave = 'leave';
    case Sick = 'sick';
    case Remote = 'remote';
    case HalfDay = 'half_day';

    public function canReceive(): bool
    {
        return in_array($this, [self::Working, self::Remote], true);
    }

    public static function options(): array
    {
        return CrmOptions::availabilityStatuses();
    }
}
