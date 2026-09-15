<?php

namespace Dth\HumanResource\Enums;

enum BusinessFunction: string
{
    case Admin = 'admin';
    case Marketing = 'marketing';
    case CustomerService = 'customer_service';
    case Sales = 'sales';
    case Finance = 'finance';
    case Technical = 'technical';
    case Other = 'other';

    public static function options(bool $includeSystem = true): array
    {
        $cases = collect(self::cases());
        if (! $includeSystem) {
            $cases = $cases->reject(fn (self $function): bool => in_array($function, [self::Admin, self::Other], true));
        }

        return $cases->mapWithKeys(fn (self $function): array => [$function->value => $function->label()])->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => \Dth\HumanResource\Support\UiText::get('business_functions.admin', 'Administration'),
            self::Marketing => \Dth\HumanResource\Support\UiText::get('business_functions.marketing', 'Marketing'),
            self::CustomerService => \Dth\HumanResource\Support\UiText::get('business_functions.customer_service', 'Customer service'),
            self::Sales => \Dth\HumanResource\Support\UiText::get('business_functions.sales', 'Sales'),
            self::Finance => \Dth\HumanResource\Support\UiText::get('business_functions.finance', 'Finance'),
            self::Technical => \Dth\HumanResource\Support\UiText::get('business_functions.technical', 'Technical'),
            self::Other => \Dth\HumanResource\Support\UiText::get('business_functions.other', 'Other'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Marketing => 'info',
            self::CustomerService => 'warning',
            self::Sales => 'success',
            self::Finance => 'primary',
            self::Technical, self::Other => 'gray',
        };
    }
}
