<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

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

    public function defaultColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Marketing => 'primary',
            self::CustomerService => 'info',
            self::Sales => 'warning',
            self::Finance => 'success',
            self::Technical => 'gray',
            self::Other => 'gray',
        };
    }

    public function color(): string
    {
        return BadgePalette::departmentFunction($this, $this->defaultColor());
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
