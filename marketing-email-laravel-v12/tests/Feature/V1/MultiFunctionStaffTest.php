<?php

namespace Tests\Feature\V1;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\UserRole;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use App\Models\Crm\StaffBusinessFunction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiFunctionStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_employee_can_hold_multiple_business_functions_with_one_primary_function(): void
    {
        $department = Department::query()->create([
            'code' => 'startup_team',
            'name' => 'Nhóm vận hành Startup',
            'function_key' => DepartmentFunction::Other->value,
            'is_active' => true,
        ]);
        $position = Position::query()->create([
            'department_id' => $department->id,
            'title' => 'Co-founder',
            'authority_level' => PositionAuthority::Manager->value,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => UserRole::User->value,
            'is_active' => true,
        ]);
        $staff = Staff::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        StaffBusinessFunction::query()->create([
            'staff_id' => $staff->id,
            'function_key' => DepartmentFunction::Marketing->value,
            'authority_level' => PositionAuthority::Manager->value,
            'is_primary' => false,
            'is_active' => true,
        ]);
        StaffBusinessFunction::query()->create([
            'staff_id' => $staff->id,
            'function_key' => DepartmentFunction::Sales->value,
            'authority_level' => PositionAuthority::Manager->value,
            'is_primary' => true,
            'is_active' => true,
        ]);
        StaffBusinessFunction::query()->create([
            'staff_id' => $staff->id,
            'function_key' => DepartmentFunction::Finance->value,
            'authority_level' => PositionAuthority::Member->value,
            'is_primary' => false,
            'is_active' => true,
        ]);

        $user->refresh();

        $this->assertTrue($user->isMarketingManager());
        $this->assertTrue($user->isSalesManager());
        $this->assertTrue($user->isFinanceStaff());
        $this->assertFalse($user->isCustomerServiceStaff());
        $this->assertSame(DepartmentFunction::Sales, $user->primaryBusinessFunction());
    }

    public function test_marking_a_new_primary_function_clears_the_previous_primary(): void
    {
        $user = User::factory()->create(['role' => UserRole::User->value]);
        $staff = Staff::factory()->create(['user_id' => $user->id]);

        $sales = StaffBusinessFunction::query()->create([
            'staff_id' => $staff->id,
            'function_key' => DepartmentFunction::Sales->value,
            'authority_level' => PositionAuthority::Member->value,
            'is_primary' => true,
            'is_active' => true,
        ]);
        StaffBusinessFunction::query()->create([
            'staff_id' => $staff->id,
            'function_key' => DepartmentFunction::Marketing->value,
            'authority_level' => PositionAuthority::Member->value,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->assertFalse($sales->fresh()->is_primary);
        $this->assertSame(1, $staff->businessFunctions()->where('is_primary', true)->count());
        $this->assertSame(DepartmentFunction::Marketing, $staff->fresh()->primaryBusinessFunction());
    }

    public function test_explicit_capability_rows_override_physical_department_permissions(): void
    {
        $department = Department::query()->create([
            'code' => 'sales_department',
            'name' => 'Phòng Kinh doanh',
            'function_key' => DepartmentFunction::Sales->value,
            'is_active' => true,
        ]);
        $position = Position::query()->create([
            'department_id' => $department->id,
            'title' => 'Nhân viên',
            'authority_level' => PositionAuthority::Member->value,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['role' => UserRole::User->value]);
        $staff = Staff::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        StaffBusinessFunction::query()->create([
            'staff_id' => $staff->id,
            'function_key' => DepartmentFunction::Marketing->value,
            'authority_level' => PositionAuthority::Member->value,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $staff->refresh()->load('businessFunctions');

        $this->assertTrue($staff->hasBusinessFunction(DepartmentFunction::Marketing));
        $this->assertFalse($staff->hasBusinessFunction(DepartmentFunction::Sales));
        $this->assertFalse($user->fresh()->isSalesStaff());
    }
}
