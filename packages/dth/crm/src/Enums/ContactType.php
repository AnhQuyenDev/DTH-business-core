<?php

namespace Dth\Crm\Enums;

use Dth\Crm\Support\CrmOptions;

enum ContactType: string
{
    case Personal = 'personal';
    case Business = 'business';

    public static function options(): array
    {
        return CrmOptions::contactTypes();
    }
}
