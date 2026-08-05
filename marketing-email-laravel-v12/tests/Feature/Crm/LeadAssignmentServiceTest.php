<?php

namespace Tests\Feature\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\ContactType;
use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\User;
use App\Services\Crm\CompanyOwnershipService;
use App\Services\Crm\LeadAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeadAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $leadSequence = 0;

    private function makeStaff(string $code): Staff
    {
        $user = User::factory()->create([
            'role' => 'customer_service_staff',
        ]);

        return Staff::query()->create([
            'user_id' => $user->id,
            'employee_code' => $code,
            'full_name' => 'Staff '.$code,
            'employment_status' => StaffEmploymentStatus::Active->value,
            'can_receive_customers' => true,
            'distribution_weight' => 1,
        ]);
    }

    private function makeCompany(): Company
    {
        return Company::query()->create([
            'company_code' => 'COM-TEST-'.uniqid(),
            'legal_name' => 'Công ty Phase 4',
            'lifecycle_stage' => 'prospect',
        ]);
    }

    private function makeLead(?Company $company = null): Lead
    {
        $this->leadSequence++;

        $contact = Contact::query()->create([
            'contact_type' => ContactType::Personal->value,
        ]);

        $lead = Lead::query()->create([
            'lead_code' => sprintf(
                'LEAD-P4-%06d',
                $this->leadSequence
            ),
            'contact_id' => $contact->id,
            'company_id' => $company?->id,
            'source' => 'manual',
            'title' => 'Lead Phase 4',
            'intake_status' => LeadIntakeStatus::New->value,
        ]);

        ContactQualification::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'status' => ContactQualificationStatus::New->value,
            'priority' => 'normal',
        ]);

        return $lead->fresh('qualification');
    }

    public function test_first_assignment_creates_company_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = $this->makeStaff('CS001');
        $company = $this->makeCompany();
        $lead = $this->makeLead($company);

        $result = app(LeadAssignmentService::class)->assign(
            lead: $lead,
            staff: $staff,
            assignedByUserId: $admin->id,
        );

        $this->assertSame($staff->id, $result->assigned_staff_id);
        $this->assertSame(LeadIntakeStatus::Active, $result->intake_status);
        $this->assertNotNull($result->assigned_at);
        $this->assertSame($admin->id, $result->assigned_by_user_id);

        $this->assertDatabaseHas('contact_qualifications', [
            'lead_id' => $lead->id,
            'assigned_staff_id' => $staff->id,
            'status' => ContactQualificationStatus::Assigned->value,
        ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'account_owner_staff_id' => $staff->id,
        ]);

        $this->assertDatabaseHas('company_assignments', [
            'company_id' => $company->id,
            'staff_id' => $staff->id,
            'assignment_type' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_company_lead_cannot_be_assigned_to_other_staff_without_force(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = $this->makeStaff('CS001');
        $other = $this->makeStaff('CS002');
        $company = $this->makeCompany();

        app(CompanyOwnershipService::class)->assignOwner(
            company: $company,
            staff: $owner,
            reason: 'Owner hiện tại',
            assignedByUserId: $admin->id,
        );

        $lead = $this->makeLead($company);

        $this->expectException(ValidationException::class);

        app(LeadAssignmentService::class)->assign(
            lead: $lead,
            staff: $other,
            assignedByUserId: $admin->id,
        );
    }

    public function test_force_reassignment_does_not_change_company_owner_by_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = $this->makeStaff('CS001');
        $other = $this->makeStaff('CS002');
        $company = $this->makeCompany();
        $lead = $this->makeLead($company);

        app(LeadAssignmentService::class)->assign(
            lead: $lead,
            staff: $owner,
            assignedByUserId: $admin->id,
        );

        app(LeadAssignmentService::class)->assign(
            lead: $lead,
            staff: $other,
            assignedByUserId: $admin->id,
            reason: 'Điều chuyển chuyên môn',
            force: true,
            transferCompanyOwner: false,
        );

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'assigned_staff_id' => $other->id,
        ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'account_owner_staff_id' => $owner->id,
        ]);
    }

    public function test_force_reassignment_can_transfer_company_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = $this->makeStaff('CS001');
        $other = $this->makeStaff('CS002');
        $company = $this->makeCompany();
        $lead = $this->makeLead($company);

        app(LeadAssignmentService::class)->assign(
            lead: $lead,
            staff: $owner,
            assignedByUserId: $admin->id,
        );

        app(LeadAssignmentService::class)->assign(
            lead: $lead,
            staff: $other,
            assignedByUserId: $admin->id,
            reason: 'Chuyển toàn bộ Account Owner',
            force: true,
            transferCompanyOwner: true,
        );

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'account_owner_staff_id' => $other->id,
        ]);

        $this->assertDatabaseHas('company_assignments', [
            'company_id' => $company->id,
            'staff_id' => $owner->id,
            'status' => 'ended',
        ]);

        $this->assertDatabaseHas('company_assignments', [
            'company_id' => $company->id,
            'staff_id' => $other->id,
            'status' => 'active',
        ]);
    }

    public function test_force_reassignment_requires_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = $this->makeStaff('CS001');
        $second = $this->makeStaff('CS002');
        $lead = $this->makeLead();

        app(LeadAssignmentService::class)->assign(
            lead: $lead,
            staff: $first,
            assignedByUserId: $admin->id,
        );

        $this->expectException(ValidationException::class);

        app(LeadAssignmentService::class)->assign(
            lead: $lead,
            staff: $second,
            assignedByUserId: $admin->id,
            force: true,
        );
    }
}
