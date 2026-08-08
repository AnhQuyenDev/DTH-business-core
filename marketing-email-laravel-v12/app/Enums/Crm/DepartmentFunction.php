<?php

namespace App\Enums\Crm;

enum DepartmentFunction: string
{
    case Admin = 'admin';
    case Marketing = 'marketing';
    case CustomerService = 'customer_service';
    case Sales = 'sales';
    case Finance = 'finance';
    case Technical = 'technical';
    case Other = 'other';

    public function label(): string
    {
        return __('enum.department_function.'.$this->value);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(
                fn (self $function): array => [
                    $function->value => $function->label(),
                ]
            )
            ->all();
    }
}
