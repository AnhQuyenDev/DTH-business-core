<?php

namespace Tests\Feature\Sales\Concerns;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Crm\QualificationResult;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationPaymentNotice;
use App\Models\User;
use Tests\Support\MakesV1Actors;

trait PaymentConversionSetup
{
    use MakesV1Actors;

    private function makeAdminUser(): User
    {
        return $this->makeV1SystemAdmin('Test System Admin');
    }

    private function makeStaff(?User $user = null): Staff
    {
        if ($user?->staff !== null) {
            return $user->staff;
        }

        // Business workflow fixtures always use a canonical Sales actor.
        // System accounts remain infrastructure-only and are never granted
        // implicit commercial authority by a test helper.
        return $this->makeV1SalesStaff('Payment Flow Sales Staff')[1];
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
            ],
        );

        return $contact->fresh('personalProfile');
    }

    /**
     * Builds a canonical convertible flow through Accepted, but deliberately
     * stops before customer conversion. Paid is the only conversion boundary.
     *
     * @return array{admin:User,company:?Company,staff:Staff,contact:Contact,
     *               lead:\App\Models\Crm\Lead,qualification:ContactQualification,
     *               opportunity:Opportunity,quotation:Quotation}
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

        $lead = \App\Models\Crm\Lead::query()->create([
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
            'budget_status' => 'confirmed_fit',
            'budget_amount' => 30_000_000,
            'purchase_timeline' => 'within_30_days',
            'decision_role' => 'decision_maker',
            'qualification_note' => 'Đủ nhu cầu, ngân sách, thời gian và vai trò quyết định.',
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
            'payment_status' => PaymentStatus::Unpaid->value,
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

        return compact(
            'admin', 'company', 'staff', 'contact', 'lead',
            'qualification', 'opportunity', 'quotation',
        );
    }

    /**
     * Put an Accepted quotation into the exact state produced by the public
     * payment-notice step. Paid tests should always call this first.
     */
    private function preparePendingPaymentNotice(Quotation $quotation): QuotationPaymentNotice
    {
        $quotation->update([
            'payment_status' => PaymentStatus::PendingVerification->value,
        ]);

        return $quotation->paymentNotices()->create([
            'status' => PaymentNoticeStatus::Pending->value,
            'payer_name' => 'Nguyễn Minh Khoa',
            'payer_email' => $quotation->party_email,
            'declared_amount' => $quotation->grand_total,
            'transfer_reference' => 'TEST-'.fake()->unique()->numerify('######'),
            'submitted_at' => now(),
        ]);
    }
}
