<?php

namespace App\Enums\Crm;

enum CompanyLifecycleStage: string
{
    case Prospect = 'prospect';
    case Qualified = 'qualified';
    case Customer = 'customer';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Tiềm năng',
            self::Qualified => 'Đủ điều kiện',
            self::Customer => 'Khách hàng',
            self::Inactive => 'Không hoạt động',
        };
    }
}
