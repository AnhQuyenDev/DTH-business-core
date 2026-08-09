<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum CompanyLifecycleStage: string
{
    case Prospect = 'prospect';
    case Qualified = 'qualified';
    case Customer = 'customer';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __('enum.company_lifecycle.'.$this->value);
    }

    public function color(): string
    {
        return BadgePalette::status($this);
    }

}
