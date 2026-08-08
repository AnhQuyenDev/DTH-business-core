<?php

namespace Tests\Feature\Configuration;

use App\Enums\UserRole;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Services\Organization\RoleDepartmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemRoleOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_generic_system_roles_are_assignable(): void
    {
        $this->assertSame([
            'admin',
            'executive',
            'user',
            'viewer',
        ], array_keys(UserRole::options()));
    }

    public function test_business_permissions_come_from_department_and_position_authority(): void
    {
        $sales = Department::query()->create([
            'code' => 'sales_test_new_model',
            'name' => 'Kinh doanh Test',
            'function_key' => 'sales',
            'color' => 'success',
            'is_active' => true,
        ]);

        $managerPosition = Position::query()->create([
            'title' => 'Trưởng phòng',
            'authority_level' => 'manager',
            'department_id' => $sales->id,
            'is_active' => true,
        ]);

        $staffPosition = Position::query()->create([
            'title' => 'Nhân viên',
            'authority_level' => 'member',
            'department_id' => $sales->id,
            'is_active' => true,
        ]);

        $managerUser = User::factory()->create(['role' => 'user']);
        Staff::query()->create([
            'user_id' => $managerUser->id,
            'employee_code' => 'EMP-9101',
            'full_name' => 'Sales Manager New Model',
            'department_id' => $sales->id,
            'position_id' => $managerPosition->id,
            'employment_status' => 'active',
            'can_receive_customers' => true,
            'distribution_weight' => 1,
        ]);

        $staffUser = User::factory()->create(['role' => 'user']);
        Staff::query()->create([
            'user_id' => $staffUser->id,
            'employee_code' => 'EMP-9102',
            'full_name' => 'Sales Staff New Model',
            'department_id' => $sales->id,
            'position_id' => $staffPosition->id,
            'employment_status' => 'active',
            'can_receive_customers' => true,
            'distribution_weight' => 1,
        ]);

        $this->assertTrue($managerUser->fresh()->isSalesManager());
        $this->assertTrue($managerUser->fresh()->isSalesStaff());

        $this->assertFalse($staffUser->fresh()->isSalesManager());
        $this->assertTrue($staffUser->fresh()->isSalesStaff());
    }

    public function test_generic_user_and_executive_require_staff_profile(): void
    {
        $service = app(RoleDepartmentService::class);

        $this->assertTrue($service->requiresStaff('user'));
        $this->assertTrue($service->requiresStaff('executive'));
        $this->assertFalse($service->requiresStaff('admin'));
        $this->assertFalse($service->requiresStaff('viewer'));
    }

    public function test_executive_is_cross_department_reader_but_not_department_manager(): void
    {
        $executive = User::factory()->create([
            'role' => 'executive',
            'is_active' => true,
        ]);

        $this->assertTrue($executive->canReadAcrossBusiness());
        $this->assertTrue($executive->canViewMarketingModule());
        $this->assertTrue($executive->canViewCrmModule());
        $this->assertTrue($executive->canViewSalesModule());
        $this->assertFalse($executive->isSalesManager());
        $this->assertFalse($executive->isCustomerServiceManager());
    }
}
