<?php

namespace Tests\Feature\Configuration;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\UserRole;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use App\Models\Crm\StaffBusinessFunction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class V1OrganizationLifecycleTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    public function test_staff_profile_can_exist_without_login_account(): void
    {
        $department = Department::factory()->create([
            'function_key' => DepartmentFunction::Sales->value,
        ]);
        $staff = Staff::factory()->create([
            'department_id' => $department->id,
            'user_id' => null,
        ]);

        $this->assertNull($staff->user_id);
        $this->assertDatabaseHas('staff', ['id' => $staff->id, 'user_id' => null]);
    }

    public function test_deleting_login_account_preserves_employee_history_by_detaching_staff(): void
    {
        [$user, $staff] = $this->makeV1SalesStaff();

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('staff', ['id' => $staff->id, 'user_id' => null]);
    }

    public function test_business_functions_are_explicit_capabilities_and_can_cross_physical_departments(): void
    {
        [$user, $staff] = $this->makeV1BusinessActor(
            'Commercial Multi Function',
            [
                [DepartmentFunction::Sales, PositionAuthority::Member, true],
                [DepartmentFunction::Marketing, PositionAuthority::Member, false],
            ],
            DepartmentFunction::Sales,
        );

        $this->assertTrue($staff->hasBusinessFunction(DepartmentFunction::Sales));
        $this->assertTrue($staff->hasBusinessFunction(DepartmentFunction::Marketing));
        $this->assertFalse($staff->hasBusinessFunction(DepartmentFunction::Finance));
        $this->assertTrue($user->can('sales.create-quotations'));
        $this->assertTrue($user->can('marketing.manage-campaigns'));
        $this->assertFalse($user->can('sales.verify-payments'));
    }

    public function test_explicit_business_function_rows_override_physical_department_fallback(): void
    {
        [$user, $staff] = $this->makeV1BusinessActor(
            'Physical Sales but Marketing Capability',
            [[DepartmentFunction::Marketing, PositionAuthority::Member, true]],
            DepartmentFunction::Sales,
        );

        $this->assertSame(DepartmentFunction::Sales, $staff->department->function());
        $this->assertTrue($staff->hasBusinessFunction(DepartmentFunction::Marketing));
        $this->assertFalse($staff->hasBusinessFunction(DepartmentFunction::Sales));
        $this->assertTrue($user->can('marketing.manage-campaigns'));
        $this->assertFalse($user->can('sales.create-quotations'));
    }

    public function test_only_one_active_primary_business_function_is_retained(): void
    {
        [$user, $staff] = $this->makeV1SalesStaff();

        StaffBusinessFunction::query()->create([
            'staff_id' => $staff->id,
            'function_key' => DepartmentFunction::Marketing->value,
            'authority_level' => PositionAuthority::Member->value,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->assertSame(1, $staff->businessFunctions()->where('is_primary', true)->count());
        $this->assertSame(
            DepartmentFunction::Marketing,
            $staff->businessFunctions()->where('is_primary', true)->value('function_key'),
        );
        $this->assertTrue($user->fresh()->can('marketing.manage-campaigns'));
    }

    public function test_inactive_user_cannot_access_business_panel(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin->value,
            'is_active' => false,
        ]);

        $this->assertFalse($user->canAccessBusinessPanel());
    }

    public function test_inactive_staff_is_not_eligible_to_receive_work(): void
    {
        [, $staff] = $this->makeV1SalesStaff();
        $staff->update([
            'employment_status' => StaffEmploymentStatus::Inactive->value,
            'can_receive_customers' => true,
        ]);

        $this->assertFalse($staff->fresh()->isAvailable());
    }

    public function test_position_authority_and_business_function_authority_are_independent(): void
    {
        [$user, $staff] = $this->makeV1BusinessActor(
            'Physical Manager but Functional Member',
            [[DepartmentFunction::Sales, PositionAuthority::Member, true]],
        );
        $staff->position->update(['authority_level' => PositionAuthority::Manager->value]);

        $this->assertTrue($staff->fresh()->position->grantsDepartmentManagerAuthority());
        $this->assertFalse($user->fresh()->hasBusinessManagerAuthority(DepartmentFunction::Sales));
        $this->assertFalse($user->fresh()->can('sales.approve-quotations'));
    }
}
