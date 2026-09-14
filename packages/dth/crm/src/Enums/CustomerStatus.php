<?php

namespace Dth\Crm\Enums;

use Dth\Crm\Support\CrmOptions;

enum CustomerStatus: string
{
    case Potential = 'potential';
    case Active = 'active';
    case Inactive = 'inactive';
    case Churned = 'churned';

    public static function options(): array
    {
        return CrmOptions::customerStatuses();
    }
}
