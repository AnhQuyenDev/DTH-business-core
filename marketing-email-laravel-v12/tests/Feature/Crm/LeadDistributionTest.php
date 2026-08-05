<?php

namespace Tests\Feature\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\ContactType;
use App\Enums\Crm\DistributionStrategy;
use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Crm\StaffAvailabilityStatus;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Crm\StaffAvailability;
use App\Models\Marketing\Contact;
use App\Models\User;
use App\Services\Crm\CompanyOwnershipService;
use App\Services\Crm\LeadAssignmentService;
use App\Services\Crm\LeadDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadDistributionTest extends TestCase
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

    private function makeLead(
        ?Company $company = null,
        LeadIntakeStatus $status = LeadIntakeStatus::New,
        ContactQualificationStatus $qualificationStatus =
            ContactQualificationStatus::New,
    ): Lead {
        $this->leadSequence++;

        $contact = Contact::query()->create([
            'contact_type' => ContactType::Personal->value,
        ]);

        $lead = Lead::query()->create([
            'lead_code' => sprintf(
                'LEAD-DIST-%06d',
                $this->leadSequence
            ),
            'contact_id' => $contact->id,
            'company_id' => $company?->id,
            'source' => 'manual',
            'title' => 'Distribution test',
            'intake_status' => $status->value,
        ]);

        ContactQualification::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'status' => $qualificationStatus->value,
            'priority' => 'normal',
        ]);

        return $lead;
    }

    private function makeCompany(): Company
    {
        return Company::query()->create([
            'company_code' => 'COM-DIST-'.uniqid(),
            'legal_name' => 'Company Distribution',
            'lifecycle_stage' => 'prospect',
        ]);
    }

    public function test_company_owner_receives_new_company_lead(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = $this->makeStaff('CS001');
        $other = $this->makeStaff('CS002');
        $company = $this->makeCompany();

        app(CompanyOwnershipService::class)->assignOwner(
            company: $company,
            staff: $owner,
            reason: 'Owner ban đầu',
            assignedByUserId: $admin->id,
        );

        $lead = $this->makeLead($company);

        $result = app(LeadDistributionService::class)
            ->distributeUnassigned(
                strategy: DistributionStrategy::LeastLoaded,
                staffIds: [$owner->id, $other->id],
                assignedByUserId: $admin->id,
            );

        $this->assertSame(1, $result['assigned']);
        $this->assertSame(0, $result['skipped']);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'assigned_staff_id' => $owner->id,
        ]);
    }

    public function test_least_loaded_strategy_uses_lead_load(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staffA = $this->makeStaff('CS001');
        $staffB = $this->makeStaff('CS002');

        $existing = $this->makeLead();

        app(LeadAssignmentService::class)->assign(
            lead: $existing,
            staff: $staffA,
            assignedByUserId: $admin->id,
        );

        $newLead = $this->makeLead();

        app(LeadDistributionService::class)->distributeUnassigned(
            strategy: DistributionStrategy::LeastLoaded,
            staffIds: [$staffA->id, $staffB->id],
            assignedByUserId: $admin->id,
        );

        $this->assertDatabaseHas('leads', [
            'id' => $newLead->id,
            'assigned_staff_id' => $staffB->id,
        ]);
    }

    public function test_unavailable_company_owner_causes_skip(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = $this->makeStaff('CS001');
        $other = $this->makeStaff('CS002');
        $company = $this->makeCompany();

        app(CompanyOwnershipService::class)->assignOwner(
            company: $company,
            staff: $owner,
            reason: 'Owner ban đầu',
            assignedByUserId: $admin->id,
        );

        StaffAvailability::query()->create([
            'staff_id' => $owner->id,
            'status' => StaffAvailabilityStatus::Leave->value,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'can_receive_new_customers' => false,
            'can_support_customers' => false,
        ]);

        $lead = $this->makeLead($company);

        $result = app(LeadDistributionService::class)
            ->distributeUnassigned(
                strategy: DistributionStrategy::LeastLoaded,
                staffIds: [$owner->id, $other->id],
                assignedByUserId: $admin->id,
            );

        $this->assertSame(0, $result['assigned']);
        $this->assertSame(1, $result['skipped']);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'assigned_staff_id' => null,
        ]);
    }

    public function test_terminal_leads_are_not_distributed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = $this->makeStaff('CS001');

        $spamLead = $this->makeLead(
            status: LeadIntakeStatus::Spam,
            qualificationStatus: ContactQualificationStatus::Spam,
        );

        $result = app(LeadDistributionService::class)
            ->distributeUnassigned(
                staffIds: [$staff->id],
                assignedByUserId: $admin->id,
            );

        $this->assertSame(0, $result['total']);
        $this->assertDatabaseHas('leads', [
            'id' => $spamLead->id,
            'assigned_staff_id' => null,
        ]);
    }
}
