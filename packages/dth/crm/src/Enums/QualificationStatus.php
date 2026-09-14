<?php

namespace Dth\Crm\Enums;

use Dth\Crm\Support\CrmOptions;

enum QualificationStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case Contacting = 'contacting';
    case FollowUp = 'follow_up';
    case Qualified = 'qualified';
    case Unqualified = 'unqualified';
    case Converted = 'converted';
    case Duplicate = 'duplicate';
    case Spam = 'spam';
    case Archived = 'archived';

    public static function options(): array
    {
        return CrmOptions::qualificationStatuses();
    }
}
