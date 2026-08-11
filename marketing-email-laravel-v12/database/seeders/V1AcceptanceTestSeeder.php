<?php

namespace Database\Seeders;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\UserRole;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use App\Models\Crm\StaffBusinessFunction;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookAccessRule;
use App\Models\User;
use App\Services\Security\RbacSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class V1AcceptanceTestSeeder extends Seeder
{
    public const PASSWORD = 'V1@2026Demo!';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('V1AcceptanceTestSeeder chỉ được phép chạy ngoài production.');
        }

        app(RbacSyncService::class)->syncDefinitions();
        $departments = [
            'founder_office' => ['Ban điều hành', DepartmentFunction::Other, 'primary'],
            'revenue' => ['Kinh doanh & Marketing', DepartmentFunction::Sales, 'warning'],
            'customer_success' => ['Chăm sóc khách hàng', DepartmentFunction::CustomerService, 'info'],
            'finance' => ['Tài chính', DepartmentFunction::Finance, 'success'],
        ];

        foreach ($departments as $code => [$name, $function, $color]) {
            Department::query()->updateOrCreate(['code' => $code], [
                'name' => $name,
                'function_key' => $function->value,
                'color' => $color,
                'description' => 'Dữ liệu tổ chức dùng cho V1 Acceptance Test.',
                'sort_order' => 10,
                'is_active' => true,
            ]);
        }

        $positionIds = [];
        foreach (Department::query()->whereIn('code', array_keys($departments))->get() as $department) {
            $positionIds[$department->code]['manager'] = Position::query()->updateOrCreate([
                'department_id' => $department->id,
                'title' => $department->code === 'founder_office' ? 'Giám đốc' : 'Trưởng bộ phận',
            ], [
                'authority_level' => $department->code === 'founder_office'
                    ? PositionAuthority::Executive->value
                    : PositionAuthority::Manager->value,
                'is_active' => true,
                'sort_order' => 10,
            ])->id;

            $positionIds[$department->code]['member'] = Position::query()->updateOrCreate([
                'department_id' => $department->id,
                'title' => 'Nhân viên',
            ], [
                'authority_level' => PositionAuthority::Member->value,
                'is_active' => true,
                'sort_order' => 50,
            ])->id;
        }

        $superAdmin = User::query()->updateOrCreate(['email' => 'superadmin.v1@dth.local'], [
            'name' => 'V1 Super Admin',
            'password' => Hash::make(self::PASSWORD),
            'role' => UserRole::SuperAdmin->value,
            'is_active' => true,
        ]);

        $admin = User::query()->updateOrCreate(['email' => 'admin.v1@dth.local'], [
            'name' => 'V1 System Admin',
            'password' => Hash::make(self::PASSWORD),
            'role' => UserRole::Admin->value,
            'is_active' => true,
        ]);

        $founder = $this->staffUser(
            'founder.v1@dth.local', 'Nguyễn Founder', 'founder_office',
            $positionIds['founder_office']['manager'], 'EMP-V1-001',
            [
                [DepartmentFunction::Marketing, PositionAuthority::Manager, false],
                [DepartmentFunction::Sales, PositionAuthority::Manager, true],
                [DepartmentFunction::CustomerService, PositionAuthority::Manager, false],
                [DepartmentFunction::Finance, PositionAuthority::Member, false],
            ],
            false,
        );

        $commercial = $this->staffUser(
            'commercial.v1@dth.local', 'Trần Kinh Doanh', 'revenue',
            $positionIds['revenue']['member'], 'EMP-V1-002',
            [
                [DepartmentFunction::Marketing, PositionAuthority::Member, false],
                [DepartmentFunction::Sales, PositionAuthority::Member, true],
            ],
            true,
        );

        $support = $this->staffUser(
            'support.v1@dth.local', 'Lê Chăm Sóc', 'customer_success',
            $positionIds['customer_success']['member'], 'EMP-V1-003',
            [[DepartmentFunction::CustomerService, PositionAuthority::Member, true]],
            true,
        );

        $finance = $this->staffUser(
            'finance.v1@dth.local', 'Phạm Tài Chính', 'finance',
            $positionIds['finance']['member'], 'EMP-V1-004',
            [[DepartmentFunction::Finance, PositionAuthority::Member, true]],
            false,
        );

        // The reset deliberately clears obsolete user references on preserved
        // Marketing assets. Hand those retained assets to the V1 commercial
        // test user so the acceptance flow can reuse them immediately.
        $commercialUserId = $commercial->user_id;
        MarketingCampaign::query()->whereNull('created_by')->update(['created_by' => $commercialUserId]);
        LandingPage::query()->whereNull('created_by')->update(['created_by' => $commercialUserId]);
        Campaign::query()->whereNull('created_by')->update(['created_by' => $commercialUserId]);
        FormTemplate::query()->whereNull('created_by')->update(['created_by' => $commercialUserId]);
        EmailTemplate::query()->whereNull('created_by')->update(['created_by' => $commercialUserId]);

        // Preserve Price Books but rebuild access rules against the new V1
        // organization. Sales capability is still verified in policies.
        foreach (PriceBook::query()->get() as $priceBook) {
            PriceBookAccessRule::query()->updateOrCreate([
                'price_book_id' => $priceBook->id,
                'access_type' => 'all',
            ], [
                'can_view' => true,
                'can_create_quotation' => true,
            ]);
        }

        app(RbacSyncService::class)->syncAllUsers();

        $this->command?->info('V1 accounts: superadmin/admin/founder/commercial/support/finance @dth.local');
        $this->command?->info('Common password: '.self::PASSWORD);
    }

    /** @param array<int,array{0:DepartmentFunction,1:PositionAuthority,2:bool}> $functions */
    private function staffUser(
        string $email,
        string $name,
        string $departmentCode,
        int $positionId,
        string $employeeCode,
        array $functions,
        bool $canReceiveWork = true,
    ): Staff {
        $user = User::query()->updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make(self::PASSWORD),
            'role' => UserRole::User->value,
            'is_active' => true,
        ]);

        $department = Department::query()->where('code', $departmentCode)->firstOrFail();

        $staff = Staff::query()->updateOrCreate(['employee_code' => $employeeCode], [
            'user_id' => $user->id,
            'full_name' => $name,
            'department_id' => $department->id,
            'position_id' => $positionId,
            'employment_status' => StaffEmploymentStatus::Active->value,
            'can_receive_customers' => $canReceiveWork,
            'customer_capacity' => 100,
            'distribution_weight' => 1,
            'started_at' => now()->toDateString(),
        ]);

        foreach ($functions as [$function, $authority, $primary]) {
            StaffBusinessFunction::query()->updateOrCreate([
                'staff_id' => $staff->id,
                'function_key' => $function->value,
            ], [
                'authority_level' => $authority->value,
                'is_primary' => $primary,
                'is_active' => true,
            ]);
        }

        return $staff;
    }
}
