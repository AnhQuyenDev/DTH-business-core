<?php

namespace Tests\Feature\Console;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\QualificationResult;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use App\Models\Crm\Department;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillOpportunitiesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::query()->create([
            'name' => ucfirst(str_replace('_', ' ', $role)),
            'email' => $role.'-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => $role,
        ]);
    }

    private function makeStaff(User $user): Staff
    {
        return Staff::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'NV-'.fake()->unique()->numberBetween(1000, 999999),
            'full_name' => 'Nhân viên '.fake()->lastName(),
            'department_id' => Department::query()->firstOrCreate(
                ['code' => 'sales'],
                ['name' => 'Kinh doanh', 'sort_order' => 4, 'is_active' => true]
            )->id,
            'employment_status' => 'active',
            'can_receive_customers' => true,
        ]);
    }

    private function makeCompany(Staff $owner): Company
    {
        return Company::query()->create([
            'company_code' => 'COM-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'legal_name' => 'Công ty ABC',
            'tax_code' => (string) fake()->unique()->numberBetween(1000000000, 9999999999),
            'lifecycle_stage' => 'prospect',
            'account_owner_staff_id' => $owner->id,
        ]);
    }

    private function makeQualifiedFlow(): array
    {
        $user = $this->makeUser('customer_service_staff');
        $staff = $this->makeStaff($user);
        $company = $this->makeCompany($staff);

        $contact = Contact::factory()->create();

        $lead = Lead::query()->create([
            'lead_code' => 'LEAD-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'assigned_staff_id' => $staff->id,
            'source' => 'landing_page',
            'title' => 'Yêu cầu tư vấn VPS',
            'service_interest' => 'VPS doanh nghiệp',
            'estimated_value' => 30000000,
            'intake_status' => 'new',
        ]);

        $qualification = ContactQualification::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'assigned_staff_id' => $staff->id,
            'status' => ContactQualificationStatus::Qualified->value,
            'qualification_result' => QualificationResult::ConfirmedNeed->value,
            'service_interest' => 'VPS doanh nghiệp',
            'estimated_value' => 30000000,
            'priority' => 'normal',
            'score' => 80,
            'qualified_at' => now(),
            'qualified_by_staff_id' => $staff->id,
        ]);

        return compact('lead', 'qualification', 'company', 'contact', 'staff', 'user');
    }

    public function test_command_lists_qualified_qualifications_without_opportunity_in_dry_run(): void
    {
        $this->makeQualifiedFlow();
        $this->artisan('sales:backfill-opportunities', ['--dry-run' => true])
            ->expectsOutputToContain('qualified_qualifications_without_opportunity')
            ->assertSuccessful();

        $this->assertDatabaseEmpty('sales_opportunities');
    }

    public function test_command_creates_opportunity_from_qualified_qualification(): void
    {
        $flow = $this->makeQualifiedFlow();

        $this->assertDatabaseEmpty('sales_opportunities');

        $this->artisan('sales:backfill-opportunities')->assertSuccessful();

        $this->assertDatabaseHas('sales_opportunities', [
            'lead_id' => $flow['lead']->id,
            'company_id' => $flow['company']->id,
            'assigned_staff_id' => $flow['staff']->id,
            'stage' => 'qualified',
        ]);

        $this->assertSame(
            CompanyLifecycleStage::Qualified->value,
            $flow['company']->fresh()->lifecycle_stage->value
        );
    }

    public function test_command_creates_legacy_opportunity_for_customer_with_quotation(): void
    {
        $flow = $this->makeQualifiedFlow();

        $contact = $flow['contact'];
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-'.fake()->unique()->numerify('######'),
            'contact_id' => $contact->id,
            'company_id' => $flow['company']->id,
            'customer_type' => 'business',
            'display_name' => 'Khách hàng ABC',
            'consent_status' => 'subscribed',
        ]);

        $quotation = Quotation::query()->create([
            'quotation_code' => 'QT-'.fake()->unique()->numberBetween(100000, 999999),
            'customer_id' => $customer->id,
            'assigned_staff_id' => $flow['staff']->id,
            'title' => 'Báo giá legacy',
            'version' => 1,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'currency' => 'VND',
            'subtotal' => 1000000,
            'tax_total' => 100000,
            'grand_total' => 1100000,
            'status' => 'accepted',
            'payment_status' => 'paid',
            'email_status' => 'unsent',
            'customer_snapshot' => ['display_name' => 'Khách hàng ABC'],
            'payment_snapshot' => [],
            'terms_snapshot' => [],
            'public_token' => fake()->sha256(),
            'view_count' => 0,
            'created_by' => $flow['user']->id,
        ]);

        $this->assertNull($quotation->opportunity_id);

        $this->artisan('sales:backfill-opportunities')->assertSuccessful();

        $this->assertNotNull($quotation->fresh()->opportunity_id);
        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $quotation->fresh()->opportunity_id,
            'company_id' => $flow['company']->id,
            'stage' => 'won',
        ]);

        $this->assertSame(
            CompanyLifecycleStage::Customer->value,
            $flow['company']->fresh()->lifecycle_stage->value
        );
    }

    public function test_command_is_idempotent(): void
    {
        $this->makeQualifiedFlow();

        $this->artisan('sales:backfill-opportunities')->assertSuccessful();
        $firstCount = Opportunity::query()->count();

        $this->artisan('sales:backfill-opportunities')->assertSuccessful();
        $this->assertSame($firstCount, Opportunity::query()->count());
    }
}
