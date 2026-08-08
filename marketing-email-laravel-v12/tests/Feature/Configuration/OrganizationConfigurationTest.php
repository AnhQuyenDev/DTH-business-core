<?php

namespace Tests\Feature\Configuration;

use App\Filament\Resources\Sales\ServicePackageResource;
use App\Filament\Resources\Sales\ServiceResource;
use App\Models\Crm\Department;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Services\Organization\RoleDepartmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrganizationConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_catalog_permissions_match_business_roles(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin);
        $this->assertTrue(ServiceResource::canViewAny());
        $this->assertTrue(ServiceResource::canCreate());
        $this->assertTrue(ServicePackageResource::canCreate());

        $salesManager = $this->user('sales_manager');
        $this->actingAs($salesManager);
        $this->assertTrue(ServiceResource::canViewAny());
        $this->assertTrue(ServiceResource::canCreate());
        $this->assertTrue(ServicePackageResource::canCreate());

        $salesStaff = $this->user('sales_staff');
        $this->actingAs($salesStaff);
        $this->assertTrue(ServiceResource::canViewAny());
        $this->assertFalse(ServiceResource::canCreate());
        $this->assertFalse(ServicePackageResource::canCreate());

        $marketingStaff = $this->user('marketing_staff');
        $this->actingAs($marketingStaff);
        $this->assertTrue(ServiceResource::canViewAny());
        $this->assertFalse(ServiceResource::canCreate());

        $finance = $this->user('finance_staff');
        $this->actingAs($finance);
        $this->assertFalse(Gate::allows('sales.view-services'));
    }

    public function test_staff_profile_can_exist_without_login_account(): void
    {
        $department = Department::query()->create([
            'code' => 'sales_test',
            'name' => 'Sales Test',
            'function_key' => 'sales',
            'color' => 'success',
            'is_active' => true,
        ]);

        $staff = Staff::query()->create([
            'user_id' => null,
            'employee_code' => 'EMP-9001',
            'full_name' => 'Staff Without Account',
            'department_id' => $department->id,
            'employment_status' => 'active',
            'can_receive_customers' => true,
            'distribution_weight' => 1,
        ]);

        $this->assertNull($staff->user_id);
        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'user_id' => null,
        ]);
    }

    public function test_deleting_user_detaches_staff_instead_of_deleting_employee_history(): void
    {
        $user = $this->user('viewer');
        $department = Department::query()->create([
            'code' => 'history_test',
            'name' => 'History Test',
            'function_key' => 'other',
            'color' => 'gray',
            'is_active' => true,
        ]);

        $staff = Staff::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-9002',
            'full_name' => 'Historical Staff',
            'department_id' => $department->id,
            'employment_status' => 'active',
            'can_receive_customers' => false,
            'distribution_weight' => 1,
        ]);

        $user->delete();

        $this->assertDatabaseHas('staff', ['id' => $staff->id]);
        $this->assertNull($staff->fresh()->user_id);
    }

    public function test_operational_role_must_match_department_function(): void
    {
        $salesDepartment = Department::query()->create([
            'code' => 'sales_role_test',
            'name' => 'Sales Role Test',
            'function_key' => 'sales',
            'color' => 'success',
            'is_active' => true,
        ]);

        $staff = Staff::query()->create([
            'user_id' => null,
            'employee_code' => 'EMP-9003',
            'full_name' => 'Sales Staff',
            'department_id' => $salesDepartment->id,
            'employment_status' => 'active',
            'can_receive_customers' => true,
            'distribution_weight' => 1,
        ]);

        $service = app(RoleDepartmentService::class);

        $service->assertCompatible('sales_staff', $staff);
        $this->assertTrue(true);

        $this->expectException(ValidationException::class);
        $service->assertCompatible('marketing_staff', $staff);
    }

    public function test_department_color_is_master_data(): void
    {
        $department = Department::query()->create([
            'code' => 'custom_department',
            'name' => 'Custom Department',
            'function_key' => 'other',
            'color' => 'warning',
            'is_active' => true,
        ]);

        $this->assertSame('warning', $department->fresh()->color);
        $this->assertArrayHasKey('warning', Department::colorOptions());
    }

    public function test_inactive_user_cannot_access_business_panel(): void
    {
        $user = $this->user('admin');
        $this->assertTrue($user->canAccessBusinessPanel());

        $user->update(['is_active' => false]);

        $this->assertFalse($user->fresh()->canAccessBusinessPanel());
    }
}
