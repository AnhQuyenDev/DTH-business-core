<?php

namespace Tests\Feature\Compatibility;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\QualificationResult;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class BackfillOpportunitiesCompatibilityTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    private function makeQualifiedFlow(): array
    {
        [$user, $staff] = $this->makeV1SalesStaff('Backfill Sales');
        $company = Company::query()->create([
            'company_code' => 'COM-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'legal_name' => 'Công ty ABC',
            'tax_code' => (string) fake()->unique()->numberBetween(1000000000, 9999999999),
            'lifecycle_stage' => 'prospect',
            'account_owner_staff_id' => $staff->id,
        ]);
        $contact = Contact::factory()->create();
        $lead = Lead::query()->create([
            'lead_code' => 'LEAD-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'assigned_staff_id' => $staff->id,
            'source' => 'landing_page',
            'title' => 'Yêu cầu tư vấn VPS',
            'service_interest' => 'VPS doanh nghiệp',
            'estimated_value' => 30_000_000,
            'intake_status' => 'new',
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
            'qualification_note' => 'Đủ điều kiện theo V1 hiện tại.',
            'priority' => 'normal',
            'score' => 80,
            'qualified_at' => now(),
            'qualified_by_staff_id' => $staff->id,
        ]);

        return compact('lead', 'qualification', 'company', 'contact', 'staff', 'user');
    }

    public function test_dry_run_lists_qualified_records_without_writing_opportunities(): void
    {
        $this->makeQualifiedFlow();

        $this->artisan('sales:backfill-opportunities', ['--dry-run' => true])
            ->expectsOutputToContain('qualified_qualifications_without_opportunity')
            ->assertSuccessful();

        $this->assertDatabaseEmpty('sales_opportunities');
    }

    public function test_backfill_creates_qualified_opportunity_and_is_idempotent(): void
    {
        $flow = $this->makeQualifiedFlow();

        $this->artisan('sales:backfill-opportunities')->assertSuccessful();
        $this->assertDatabaseHas('sales_opportunities', [
            'lead_id' => $flow['lead']->id,
            'company_id' => $flow['company']->id,
            'assigned_staff_id' => $flow['staff']->id,
            'stage' => 'qualified',
        ]);
        $this->assertSame(
            CompanyLifecycleStage::Qualified->value,
            $flow['company']->fresh()->lifecycle_stage->value,
        );

        $firstCount = Opportunity::query()->count();
        $this->artisan('sales:backfill-opportunities')->assertSuccessful();
        $this->assertSame($firstCount, Opportunity::query()->count());
    }

    public function test_paid_legacy_customer_quotation_backfills_a_won_opportunity(): void
    {
        $flow = $this->makeQualifiedFlow();
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-'.fake()->unique()->numerify('######'),
            'contact_id' => $flow['contact']->id,
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
            'subtotal' => 1_000_000,
            'tax_total' => 100_000,
            'grand_total' => 1_100_000,
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

        $this->artisan('sales:backfill-opportunities')->assertSuccessful();

        $this->assertNotNull($quotation->fresh()->opportunity_id);
        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $quotation->fresh()->opportunity_id,
            'company_id' => $flow['company']->id,
            'stage' => 'won',
        ]);
        $this->assertSame(
            CompanyLifecycleStage::Customer->value,
            $flow['company']->fresh()->lifecycle_stage->value,
        );
    }
}
