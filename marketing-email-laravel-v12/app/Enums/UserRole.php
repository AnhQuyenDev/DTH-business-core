<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case MarketingManager = 'marketing_manager';
    case MarketingStaff = 'marketing_staff';
    case CustomerServiceManager = 'customer_service_manager';
    case CustomerServiceStaff = 'customer_service_staff';
    case SalesManager = 'sales_manager';
    case SalesStaff = 'sales_staff';
    case FinanceStaff = 'finance_staff';
    case Viewer = 'viewer';

    public function label(): string
    {
        return __('enum.role.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::MarketingManager,
            self::CustomerServiceManager,
            self::SalesManager => 'warning',
            self::MarketingStaff,
            self::CustomerServiceStaff,
            self::SalesStaff => 'info',
            self::FinanceStaff => 'success',
            self::Viewer => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(
                fn (self $role): array => [
                    $role->value => $role->label(),
                ]
            )
            ->all();
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(
            fn (self $role): string => $role->value,
            self::cases(),
        );
    }
}
