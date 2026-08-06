<?php

namespace Tests\Feature\Sales\Concerns;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Crm\QualificationResult;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\Sales\OpportunityStage;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Department;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Models\User;

trait PaymentConversionSetup
{
    private function makeAdminUser(): User
    {
        return User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);
    }

    private function makeUser(string $role): User
    {
        return User::query()->create([
            'name' => ucfirst(str_replace('_', ' ', $role)),
            'email' => $role.'-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => $role,
        ]);
    }

    private function makeStaff(?User $user = null): Staff
    {
        $user ??= $this->makeUser('customer_service_staff');

        return Staff::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'NV-'.fake()->unique()->numberBetween(1000, 999999),
            'full_name' => 'Nhân viên '.fake()->lastName(),
            'department_id' => Department::query()->firstOrCreate(
                ['code' => 'sales'],
                ['name' => 'Kinh doanh', 'sort_order' => 4, 'is_active' => true]
            )->id,
            'employment_status' => StaffEmploymentStatus::Active,
            'can_receive_customers' => true,
        ]);
    }

    private function makeCompany(?Staff $accountOwner = null): Company
    {
        return Company::query()->create([
            'company_code' => 'COM-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'legal_name' => 'Công ty TNHH '.fake()->company(),
            'tax_code' => (string) fake()->unique()->numberBetween(1000000000, 9999999999),
            'address' => '123 Đường Lê Lợi, Quận 1, TP.HCM',
            'province' => 'Hồ Chí Minh',
            'industry' => 'Công nghệ thông tin',
            'account_owner_staff_id' => $accountOwner?->id,
            'lifecycle_stage' => 'qualified',
        ]);
    }

    private function makeBusinessContact(Company $company, ?string $email = null): Contact
    {
        $contact = Contact::factory()->create();

        BusinessContactProfile::query()->create([
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'company_name' => $company->legal_name,
            'business_email' => $email ?? 'biz-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'business_phone' => '0912345678',
            'legal_representative' => 'Giám đốc '.fake()->lastName(),
        ]);

        return $contact;
    }

    private function makePersonalContact(): Contact
    {
        $contact = Contact::factory()->create();

        $contact->personalProfile()->updateOrCreate(
            ['contact_id' => $contact->id],
            [
                'first_name' => 'Nguyễn',
                'last_name' => 'Văn '.fake()->lastName(),
                'email' => 'personal-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
                'phone' => '0912345678',
            ]
        );

        return $contact->fresh('personalProfile');
    }

    /**
     * Builds a full convertible flow: Lead → Qualification → Opportunity → Quotation.
     *
     * @return array{admin: User, company: ?Company, staff: Staff, contact: Contact,
     *               lead: Lead, qualification: ContactQualification, opportunity: Opportunity,
     *               quotation: Quotation}
     */
    private function buildFlow(
        bool $withCompany = true,
        ?Staff $accountOwner = null,
        ?Staff $opportunityOwner = null,
        ?Company $company = null,
    ): array {
        $admin = $this->makeAdminUser();
        $company ??= $withCompany ? $this->makeCompany($accountOwner) : null;
        $contact = $withCompany
            ? $this->makeBusinessContact($company)
            : $this->makePersonalContact();
        $staff = $opportunityOwner ?? $this->makeStaff();

        $lead = Lead::query()->create([
            'lead_code' => 'LEAD-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'contact_id' => $contact->id,
            'company_id' => $company?->id,
            'assigned_staff_id' => $staff->id,
            'source' => 'landing_page',
            'title' => 'Yêu cầu tư vấn VPS',
            'service_interest' => 'VPS doanh nghiệp',
            'estimated_value' => 30_000_000,
            'intake_status' => LeadIntakeStatus::ConvertedToOpportunity->value,
            'converted_to_opportunity_at' => now(),
        ]);

        $qualification = ContactQualification::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'assigned_staff_id' => $staff->id,
            'status' => ContactQualificationStatus::Qualified->value,
            'qualification_result' => QualificationResult::ConfirmedNeed->value,
            'service_interest' => 'VPS doanh nghiệp',
            'estimated_value' => 30_000_000,
            'priority' => 'normal',
            'score' => 80,
            'qualified_at' => now(),
            'qualified_by_staff_id' => $staff->id,
        ]);

        $opportunity = Opportunity::query()->create([
            'opportunity_code' => 'OPP-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'lead_id' => $lead->id,
            'company_id' => $company?->id,
            'primary_contact_id' => $contact->id,
            'assigned_staff_id' => $staff->id,
            'title' => 'Cơ hội VPS doanh nghiệp',
            'service_interest' => 'VPS doanh nghiệp',
            'stage' => OpportunityStage::Negotiation->value,
            'estimated_value' => 30_000_000,
            'probability' => 85,
            'created_by' => $admin->id,
        ]);

        $quotation = Quotation::factory()->forOpportunity($opportunity)->create([
            'status' => 'accepted',
            'accepted_at' => now(),
            'created_by' => $admin->id,
            'customer_snapshot' => [
                'id' => null,
                'display_name' => $company?->legal_name ?? $contact->full_name,
                'customer_type' => $withCompany ? 'business' : 'personal',
                'email' => $withCompany
                    ? $contact->businessProfile->business_email
                    : $contact->personalProfile->email,
                'phone' => $withCompany
                    ? $contact->businessProfile->business_phone
                    : $contact->personalProfile->phone,
            ],
        ]);

        return [
            'admin' => $admin,
            'company' => $company,
            'staff' => $staff,
            'contact' => $contact,
            'lead' => $lead,
            'qualification' => $qualification,
            'opportunity' => $opportunity,
            'quotation' => $quotation,
        ];
    }
}
