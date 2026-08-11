<?php

namespace Tests\Support;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\UserRole;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use App\Models\Crm\StaffBusinessFunction;
use App\Models\User;
use App\Services\Security\RbacSyncService;

trait MakesV1Actors
{
    /**
     * Build a canonical V1 business user: generic system role + Staff + explicit
     * staff_business_functions. This deliberately avoids the deprecated
     * department-specific values in users.role.
     *
     * @param array<int,array{0:DepartmentFunction,1:PositionAuthority,2?:bool}> $functions
     * @return array{0:User,1:Staff}
     */
    protected function makeV1BusinessActor(
        string $label,
        array $functions,
        ?DepartmentFunction $physicalDepartment = null,
        bool $canReceiveWork = true,
    ): array {
        app(RbacSyncService::class)->syncDefinitions();

        $physicalDepartment ??= $functions[0][0] ?? DepartmentFunction::Other;
        $primaryAuthority = $functions[0][1] ?? PositionAuthority::Member;
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $label) ?: 'actor');
        $unique = fake()->unique()->numberBetween(1000, 999999);

        $department = Department::query()->firstOrCreate(
            ['code' => 'test-'.$physicalDepartment->value],
            [
                'name' => 'Test '.$physicalDepartment->label(),
                'function_key' => $physicalDepartment->value,
                'color' => 'gray',
                'sort_order' => 900,
                'is_active' => true,
            ],
        );

        $position = Position::query()->firstOrCreate(
            [
                'department_id' => $department->id,
                'title' => 'Test '.$primaryAuthority->value,
            ],
            [
                'authority_level' => $primaryAuthority->value,
                'sort_order' => 900,
                'is_active' => true,
            ],
        );

        $user = User::query()->create([
            'name' => $label,
            'email' => $slug.'-'.$unique.'@example.test',
            'password' => 'V1@Test123!',
            'role' => UserRole::User->value,
            'is_active' => true,
        ]);

        $staff = Staff::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'TST-'.str_pad((string) $unique, 6, '0', STR_PAD_LEFT),
            'full_name' => $label,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_status' => StaffEmploymentStatus::Active->value,
            'can_receive_customers' => $canReceiveWork,
            'customer_capacity' => 100,
            'distribution_weight' => 1,
            'started_at' => now()->toDateString(),
        ]);

        foreach ($functions as $index => $definition) {
            [$function, $authority] = $definition;
            $primary = $definition[2] ?? $index === 0;

            StaffBusinessFunction::query()->create([
                'staff_id' => $staff->id,
                'function_key' => $function->value,
                'authority_level' => $authority->value,
                'is_primary' => $primary,
                'is_active' => true,
            ]);
        }

        app(RbacSyncService::class)->syncUserCanonicalRoles($user->fresh());

        return [$user->fresh(['staff.businessFunctions']), $staff->fresh(['businessFunctions'])];
    }

    /** @return array{0:User,1:Staff} */
    protected function makeV1SalesStaff(string $label = 'V1 Sales Staff'): array
    {
        return $this->makeV1BusinessActor($label, [
            [DepartmentFunction::Sales, PositionAuthority::Member, true],
        ]);
    }

    /** @return array{0:User,1:Staff} */
    protected function makeV1SalesManager(string $label = 'V1 Sales Manager'): array
    {
        return $this->makeV1BusinessActor($label, [
            [DepartmentFunction::Sales, PositionAuthority::Manager, true],
        ]);
    }

    /** @return array{0:User,1:Staff} */
    protected function makeV1MarketingStaff(string $label = 'V1 Marketing Staff'): array
    {
        return $this->makeV1BusinessActor($label, [
            [DepartmentFunction::Marketing, PositionAuthority::Member, true],
        ]);
    }

    /** @return array{0:User,1:Staff} */
    protected function makeV1MarketingManager(string $label = 'V1 Marketing Manager'): array
    {
        return $this->makeV1BusinessActor($label, [
            [DepartmentFunction::Marketing, PositionAuthority::Manager, true],
        ]);
    }

    /** @return array{0:User,1:Staff} */
    protected function makeV1CustomerCareStaff(string $label = 'V1 Customer Care Staff'): array
    {
        return $this->makeV1BusinessActor($label, [
            [DepartmentFunction::CustomerService, PositionAuthority::Member, true],
        ]);
    }

    /** @return array{0:User,1:Staff} */
    protected function makeV1CustomerCareManager(string $label = 'V1 Customer Care Manager'): array
    {
        return $this->makeV1BusinessActor($label, [
            [DepartmentFunction::CustomerService, PositionAuthority::Manager, true],
        ]);
    }

    /** @return array{0:User,1:Staff} */
    protected function makeV1Finance(string $label = 'V1 Finance'): array
    {
        return $this->makeV1BusinessActor($label, [
            [DepartmentFunction::Finance, PositionAuthority::Member, true],
        ], canReceiveWork: false);
    }

    protected function makeV1SystemAdmin(string $label = 'V1 System Admin'): User
    {
        app(RbacSyncService::class)->syncDefinitions();

        $user = User::factory()->create([
            'name' => $label,
            'role' => UserRole::Admin->value,
            'is_active' => true,
        ]);
        app(RbacSyncService::class)->syncUserCanonicalRoles($user);

        return $user->fresh();
    }

    protected function makeV1SuperAdmin(string $label = 'V1 Super Admin'): User
    {
        app(RbacSyncService::class)->syncDefinitions();

        $user = User::factory()->create([
            'name' => $label,
            'role' => UserRole::SuperAdmin->value,
            'is_active' => true,
        ]);
        app(RbacSyncService::class)->syncUserCanonicalRoles($user);

        return $user->fresh();
    }

    protected function makeV1Executive(string $label = 'V1 Executive'): User
    {
        app(RbacSyncService::class)->syncDefinitions();

        $user = User::factory()->create([
            'name' => $label,
            'role' => UserRole::Executive->value,
            'is_active' => true,
        ]);
        app(RbacSyncService::class)->syncUserCanonicalRoles($user);

        return $user->fresh();
    }

    protected function makeV1Viewer(string $label = 'V1 Viewer'): User
    {
        app(RbacSyncService::class)->syncDefinitions();

        $user = User::factory()->create([
            'name' => $label,
            'role' => UserRole::Viewer->value,
            'is_active' => true,
        ]);
        app(RbacSyncService::class)->syncUserCanonicalRoles($user);

        return $user->fresh();
    }
}
