<?php

namespace Dth\Crm\Enums;

use Dth\Crm\Support\CrmOptions;

enum LeadIntakeStatus: string
{
    case New = 'new';
    case Active = 'active';
    case Duplicate = 'duplicate';
    case Spam = 'spam';
    case Closed = 'closed';
    case ConvertedToOpportunity = 'converted_to_opportunity';

    public static function options(): array
    {
        return CrmOptions::leadStatuses();
    }
}
