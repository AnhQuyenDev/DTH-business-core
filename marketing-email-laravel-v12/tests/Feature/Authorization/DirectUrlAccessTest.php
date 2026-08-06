<?php

namespace Tests\Feature\Authorization;

use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\Sales\OpportunityResource;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\Crm\Company;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectUrlAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('business_flow.v2_enabled', true);
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_sales_staff_cannot_access_other_opportunity_by_url(): void
    {
        $user = $this->makeUser('sales_staff');
        Staff::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $otherOpportunity = Opportunity::factory()->create([
            'assigned_staff_id' => Staff::factory()->create()->id,
        ]);

        $this->get(
            OpportunityResource::getUrl('view', ['record' => $otherOpportunity])
        )->assertNotFound();
    }

    public function test_finance_cannot_access_opportunity_url(): void
    {
        $this->actingAs($this->makeUser('finance_staff'));

        $opportunity = Opportunity::factory()->create();

        $this->get(
            OpportunityResource::getUrl('view', ['record' => $opportunity])
        )->assertNotFound();
    }

    public function test_sales_staff_cannot_access_other_quotation_url(): void
    {
        $user = $this->makeUser('sales_staff');
        Staff::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $ownerStaff = Staff::factory()->create();
        $quotation = Quotation::factory()->create([
            'assigned_staff_id' => $ownerStaff->id,
            'created_by' => $ownerStaff->user_id,
        ]);

        $this->get(
            QuotationResource::getUrl('view', ['record' => $quotation])
        )->assertNotFound();
    }

    public function test_sales_finance_marketing_cannot_access_customer_care_url(): void
    {
        foreach (['sales_staff', 'finance_staff', 'marketing_staff'] as $role) {
            $this->actingAs($this->makeUser($role));

            $this->get('/admin/customer-care-page')->assertForbidden();
        }
    }

    public function test_cs_staff_can_access_customer_care_url(): void
    {
        $user = $this->makeUser('customer_service_staff');
        Staff::factory()->create(['user_id' => $user->id, 'employee_code' => 'STAFF-CS1']);
        $this->actingAs($user);

        $this->get('/admin/customer-care-page')->assertOk();
    }

    public function test_cs_staff_cannot_access_other_customer_by_url(): void
    {
        $user = $this->makeUser('customer_service_staff');
        Staff::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $customer = Customer::factory()->create();

        $this->get(
            CustomerResource::getUrl('view', ['record' => $customer])
        )->assertNotFound();
    }

    public function test_cs_staff_cannot_access_other_lead_by_url(): void
    {
        $user = $this->makeUser('customer_service_staff');
        Staff::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $otherStaff = Staff::factory()->create();
        $lead = Lead::factory()->create([
            'assigned_staff_id' => $otherStaff->id,
        ]);

        $this->get(
            LeadResource::getUrl('view', ['record' => $lead])
        )->assertNotFound();
    }

    public function test_marketing_cannot_access_sales_company_opportunity_urls(): void
    {
        $user = $this->makeUser('marketing_staff');
        $this->actingAs($user);

        $company = Company::query()->create([
            'company_code' => 'COM-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'legal_name' => 'Công ty ABC',
        ]);

        $this->get(
            CompanyResource::getUrl('view', ['record' => $company])
        )->assertNotFound();

        $opportunity = Opportunity::factory()->create();

        $this->get(
            OpportunityResource::getUrl('view', ['record' => $opportunity])
        )->assertNotFound();
    }
}
