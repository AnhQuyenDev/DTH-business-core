<?php

namespace App\Enums;

use App\Enums\Crm\DepartmentFunction;
use App\Support\Ui\BadgePalette;

enum UserRole: string
{
    // Vai trò hệ thống được phép cấp mới.
    case Admin = 'admin';
    case Executive = 'executive';
    case User = 'user';
    case Viewer = 'viewer';

    // Alias tương thích ngược. Không hiển thị trên form tạo/sửa User mới.
    // Giữ lại để code/tests cũ không bị vỡ trong giai đoạn chuyển đổi.
    case MarketingManager = 'marketing_manager';
    case MarketingStaff = 'marketing_staff';
    case CustomerServiceManager = 'customer_service_manager';
    case CustomerServiceStaff = 'customer_service_staff';
    case SalesManager = 'sales_manager';
    case SalesStaff = 'sales_staff';
    case FinanceStaff = 'finance_staff';

    public function label(): string
    {
        return __('enum.role.'.$this->value);
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Admin => 'danger',
            self::Executive => 'warning',
            self::User => 'info',
            self::Viewer => 'gray',

            self::MarketingManager,
            self::CustomerServiceManager,
            self::SalesManager => 'warning',

            self::MarketingStaff,
            self::CustomerServiceStaff,
            self::SalesStaff => 'info',

            self::FinanceStaff => 'success',
        };

        return BadgePalette::role($this, $fallback);
    }

    public function isAssignable(): bool
    {
        return in_array($this, [
            self::Admin,
            self::Executive,
            self::User,
            self::Viewer,
        ], true);
    }

    public function isLegacyAlias(): bool
    {
        return ! $this->isAssignable();
    }

    /**
     * Chỉ các alias cũ mới mang sẵn ý nghĩa phòng ban.
     * Vai trò mới tách hoàn toàn khỏi cơ cấu tổ chức.
     */
    public function requiredDepartmentFunction(): ?DepartmentFunction
    {
        return match ($this) {
            self::MarketingManager,
            self::MarketingStaff => DepartmentFunction::Marketing,

            self::CustomerServiceManager,
            self::CustomerServiceStaff => DepartmentFunction::CustomerService,

            self::SalesManager,
            self::SalesStaff => DepartmentFunction::Sales,

            self::FinanceStaff => DepartmentFunction::Finance,

            self::Admin,
            self::Executive,
            self::User,
            self::Viewer => null,
        };
    }

    public function requiresStaff(): bool
    {
        return match ($this) {
            self::Executive,
            self::User,
            self::MarketingManager,
            self::MarketingStaff,
            self::CustomerServiceManager,
            self::CustomerServiceStaff,
            self::SalesManager,
            self::SalesStaff,
            self::FinanceStaff => true,

            self::Admin,
            self::Viewer => false,
        };
    }

    /** @return array<int, self> */
    public static function assignableCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role): bool => $role->isAssignable(),
        ));
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::assignableCases())
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
