<?php

namespace Tests\Feature\Crm;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\Company;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Services\Crm\CompanyOwnershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompanyOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaff(
        string $code,
        StaffEmploymentStatus $status = StaffEmploymentStatus::Active,
    ): Staff {
        $user = User::factory()->create([
            'role' => 'customer_service_staff',
        ]);

        return Staff::query()->create([
            'user_id' => $user->id,
            'employee_code' => $code,
            'full_name' => 'Staff '.$code,
            'employment_status' => $status->value,
            'can_receive_customers' => true,
            'distribution_weight' => 1,
        ]);
    }

    private function makeCompany(): Company
    {
        return Company::query()->create([
            'company_code' => 'COM-OWN-'.uniqid(),
            'legal_name' => 'Company Ownership',
            'lifecycle_stage' => 'prospect',
        ]);
    }

    public function test_transfer_ends_old_assignment_and_creates_new_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = $this->makeStaff('CS001');
        $second = $this->makeStaff('CS002');
        $company = $this->makeCompany();
        $service = app(CompanyOwnershipService::class);

        $service->assignOwner(
            company: $company,
            staff: $first,
            reason: 'Owner ban đầu',
            assignedByUserId: $admin->id,
        );

        $service->transferOwner(
            company: $company,
            staff: $second,
            reason: 'Điều chuyển địa bàn',
            assignedByUserId: $admin->id,
        );

        $this->assertDatabaseHas('company_assignments', [
            'company_id' => $company->id,
            'staff_id' => $first->id,
            'status' => 'ended',
        ]);

        $this->assertDatabaseHas('company_assignments', [
            'company_id' => $company->id,
            'staff_id' => $second->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'account_owner_staff_id' => $second->id,
        ]);
    }

    public function test_assigning_same_owner_is_idempotent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = $this->makeStaff('CS001');
        $company = $this->makeCompany();
        $service = app(CompanyOwnershipService::class);

        $service->assignOwner(
            company: $company,
            staff: $staff,
            reason: 'Owner ban đầu',
            assignedByUserId: $admin->id,
        );

        $service->assignOwner(
            company: $company,
            staff: $staff,
            reason: 'Gọi lại service',
            assignedByUserId: $admin->id,
        );

        $this->assertSame(
            1,
            $company->assignments()
                ->where('assignment_type', 'owner')
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_inactive_staff_cannot_become_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = $this->makeStaff(
            'CS001',
            StaffEmploymentStatus::Inactive,
        );
        $company = $this->makeCompany();

        $this->expectException(ValidationException::class);

        app(CompanyOwnershipService::class)->assignOwner(
            company: $company,
            staff: $staff,
            reason: 'Không hợp lệ',
            assignedByUserId: $admin->id,
        );
    }

    public function test_owner_assignment_requires_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = $this->makeStaff('CS001');
        $company = $this->makeCompany();

        $this->expectException(ValidationException::class);

        app(CompanyOwnershipService::class)->assignOwner(
            company: $company,
            staff: $staff,
            reason: '',
            assignedByUserId: $admin->id,
        );
    }
}
