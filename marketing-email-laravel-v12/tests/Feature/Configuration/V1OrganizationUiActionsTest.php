<?php

namespace Tests\Feature\Configuration;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\UserRole;
use App\Filament\Resources\DepartmentResource;
use App\Livewire\DepartmentPositionsTable;
use App\Livewire\DepartmentStaffTable;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class V1OrganizationUiActionsTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    public function test_system_admin_can_open_organization_configuration_surfaces(): void
    {
        $this->actingAs($this->makeV1SystemAdmin());

        $this->get('/admin/departments')->assertOk();
        $this->get('/admin/positions')->assertOk();
        $this->get('/admin/staff')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->assertTrue(DepartmentResource::canCreate());
    }

    public function test_marketing_manager_cannot_open_system_organization_configuration(): void
    {
        $actor = $this->makeV1MarketingManager()[0];

        $this->actingAs($actor);
        $this->get('/admin/departments')->assertForbidden();
        $this->get('/admin/positions')->assertForbidden();
        $this->get('/admin/staff')->assertForbidden();
    }

    public function test_sales_manager_cannot_open_system_organization_configuration(): void
    {
        $actor = $this->makeV1SalesManager()[0];

        $this->actingAs($actor);
        $this->get('/admin/departments')->assertForbidden();
        $this->get('/admin/positions')->assertForbidden();
        $this->get('/admin/staff')->assertForbidden();
    }

    public function test_customer_care_manager_cannot_open_system_organization_configuration(): void
    {
        $actor = $this->makeV1CustomerCareManager()[0];

        $this->actingAs($actor);
        $this->get('/admin/departments')->assertForbidden();
        $this->get('/admin/positions')->assertForbidden();
        $this->get('/admin/staff')->assertForbidden();
    }

    public function test_finance_staff_cannot_open_system_organization_configuration(): void
    {
        $actor = $this->makeV1Finance()[0];

        $this->actingAs($actor);
        $this->get('/admin/departments')->assertForbidden();
        $this->get('/admin/positions')->assertForbidden();
        $this->get('/admin/staff')->assertForbidden();
    }

    public function test_create_position_inside_department_binds_the_department(): void
    {
        $this->actingAs($this->makeV1SystemAdmin());
        $department = Department::factory()->create([
            'function_key' => DepartmentFunction::CustomerService->value,
        ]);

        Livewire::test(DepartmentPositionsTable::class, ['departmentId' => $department->id])
            ->callTableAction('create', data: [
                'title' => 'Nhân viên chăm sóc khách hàng',
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('positions', [
            'department_id' => $department->id,
            'title' => 'Nhân viên chăm sóc khách hàng',
        ]);
    }

    public function test_create_staff_inside_department_binds_position_and_keeps_login_optional(): void
    {
        $this->actingAs($this->makeV1SystemAdmin());
        $department = Department::factory()->create([
            'function_key' => DepartmentFunction::CustomerService->value,
        ]);
        $position = Position::factory()->create([
            'department_id' => $department->id,
            'authority_level' => PositionAuthority::Member->value,
            'is_active' => true,
        ]);

        Livewire::test(DepartmentStaffTable::class, ['departmentId' => $department->id])
            ->callTableAction('create', data: [
                'user_id' => null,
                'position_id' => $position->id,
                'full_name' => 'Nhân viên không có tài khoản',
                'employment_status' => 'active',
                'can_receive_customers' => true,
                'distribution_weight' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('staff', [
            'department_id' => $department->id,
            'position_id' => $position->id,
            'full_name' => 'Nhân viên không có tài khoản',
            'user_id' => null,
        ]);
    }

    public function test_staff_create_rejects_position_from_another_department(): void
    {
        $this->actingAs($this->makeV1SystemAdmin());
        $care = Department::factory()->create(['function_key' => DepartmentFunction::CustomerService->value]);
        $sales = Department::factory()->create(['function_key' => DepartmentFunction::Sales->value]);
        $salesPosition = Position::factory()->create([
            'department_id' => $sales->id,
            'authority_level' => PositionAuthority::Member->value,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['role' => UserRole::User->value]);

        Livewire::test(DepartmentStaffTable::class, ['departmentId' => $care->id])
            ->callTableAction('create', data: [
                'user_id' => $user->id,
                'position_id' => $salesPosition->id,
                'full_name' => 'Sai chức danh',
                'employment_status' => 'active',
            ])
            ->assertHasTableActionErrors(['position_id']);
    }
}
