<?php

namespace Tests\Feature\Authorization;

use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerAssignmentType;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\Sales\OpportunityResource;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\Lead;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class V1DirectUrlAuthorizationTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    private function assertDeniedOrHidden(TestResponse $response): void
    {
        $this->assertContains(
            $response->getStatusCode(),
            [403, 404],
            'Unauthorized direct URL must be forbidden or hidden as not found.',
        );
    }

    public function test_sales_staff_cannot_open_other_staff_opportunity_or_quotation_by_url(): void
    {
        [$sales] = $this->makeV1SalesStaff('Sales A');
        [$other, $otherStaff] = $this->makeV1SalesStaff('Sales B');
        $opportunity = Opportunity::factory()->create(['assigned_staff_id' => $otherStaff->id]);
        $quotation = Quotation::factory()->forOpportunity($opportunity)->create([
            'assigned_staff_id' => $otherStaff->id,
            'created_by' => $other->id,
        ]);

        $this->actingAs($sales);

        $this->assertDeniedOrHidden($this->get(
            OpportunityResource::getUrl('view', ['record' => $opportunity]),
        ));
        $this->assertDeniedOrHidden($this->get(
            QuotationResource::getUrl('view', ['record' => $quotation]),
        ));
    }

    public function test_finance_cannot_open_opportunity_directly(): void
    {
        $opportunity = Opportunity::factory()->create();
        [$finance] = $this->makeV1Finance();

        $this->actingAs($finance);
        $this->assertDeniedOrHidden($this->get(
            OpportunityResource::getUrl('view', ['record' => $opportunity]),
        ));
    }

    public function test_customer_care_cannot_open_opportunity_directly(): void
    {
        $opportunity = Opportunity::factory()->create();
        [$support] = $this->makeV1CustomerCareStaff();

        $this->actingAs($support);
        $this->assertDeniedOrHidden($this->get(
            OpportunityResource::getUrl('view', ['record' => $opportunity]),
        ));
    }

    public function test_customer_care_staff_can_open_assigned_customer_but_not_unassigned_customer(): void
    {
        [$support, $staff] = $this->makeV1CustomerCareStaff();
        $mine = Customer::factory()->create();
        $other = Customer::factory()->create();
        CustomerAssignment::factory()->create([
            'customer_id' => $mine->id,
            'staff_id' => $staff->id,
            'assignment_type' => CustomerAssignmentType::Owner->value,
            'status' => CustomerAssignmentStatus::Active->value,
            'starts_at' => now(),
        ]);

        $this->actingAs($support);

        $this->get(CustomerResource::getUrl('view', ['record' => $mine]))->assertOk();
        $this->assertDeniedOrHidden($this->get(
            CustomerResource::getUrl('view', ['record' => $other]),
        ));
    }

    public function test_customer_care_staff_cannot_open_pre_sales_lead_directly(): void
    {
        [$support] = $this->makeV1CustomerCareStaff();
        $lead = Lead::factory()->create();

        $this->actingAs($support);
        $this->assertDeniedOrHidden($this->get(
            LeadResource::getUrl('view', ['record' => $lead]),
        ));
    }

    public function test_customer_care_page_is_available_to_customer_care_staff(): void
    {
        [$support] = $this->makeV1CustomerCareStaff();

        $this->actingAs($support);
        $this->get('/admin/customer-care-page')->assertOk();
    }

    public function test_customer_care_page_is_available_to_system_admin_and_cross_business_readers(): void
    {
        foreach ([$this->makeV1SystemAdmin(), $this->makeV1Executive(), $this->makeV1Viewer()] as $reader) {
            $this->actingAs($reader);
            $this->get('/admin/customer-care-page')->assertOk();
        }
    }

    public function test_sales_staff_cannot_open_customer_care_page(): void
    {
        [$sales] = $this->makeV1SalesStaff();

        $this->actingAs($sales);
        $this->assertDeniedOrHidden($this->get('/admin/customer-care-page'));
    }

    public function test_finance_cannot_open_customer_care_page(): void
    {
        [$finance] = $this->makeV1Finance();

        $this->actingAs($finance);
        $this->assertDeniedOrHidden($this->get('/admin/customer-care-page'));
    }
}
