<?php

namespace App\Services\Crm;

use App\Models\Crm\Company;

final class CompanyCodeGenerator
{
    public function next(): string
    {
        $year = now()->format('Y');

        $count = Company::query()
            ->where('company_code', 'like', 'COM-'.$year.'-%')
            ->count();

        return sprintf('COM-%s-%06d', $year, $count + 1);
    }
}
