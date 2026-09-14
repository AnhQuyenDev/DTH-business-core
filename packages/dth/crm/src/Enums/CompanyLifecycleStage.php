<?php

namespace Dth\Crm\Enums;

use Dth\Crm\Support\CrmOptions;

enum CompanyLifecycleStage: string
{
    case Prospect = 'prospect';
    case Qualified = 'qualified';
    case Customer = 'customer';
    case Inactive = 'inactive';

    public static function options(): array
    {
        return CrmOptions::companyLifecycleStages();
    }
}
