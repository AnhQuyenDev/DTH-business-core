<?php

namespace Dth\Crm\Enums;

use Dth\Crm\Support\CrmOptions;

enum LeadActivityType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Message = 'message';
    case Note = 'note';
    case Other = 'other';

    public static function options(): array
    {
        return CrmOptions::activityTypes();
    }
}
