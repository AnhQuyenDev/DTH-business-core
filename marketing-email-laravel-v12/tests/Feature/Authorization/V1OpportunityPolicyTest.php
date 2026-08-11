<?php

namespace Tests\Feature\Authorization;

use App\Models\Sales\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class V1OpportunityPolicyTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    public function test_sales_staff_only_views_processes_and_quotes_assigned_opportunity(): void
    {
        [$sales, $staff] = $this->makeV1SalesStaff();
        [, $otherStaff] = $this->makeV1SalesStaff('Other Sales');
        $mine = Opportunity::factory()->create(['assigned_staff_id' => $staff->id]);
        $other = Opportunity::factory()->create(['assigned_staff_id' => $otherStaff->id]);

        $this->assertTrue($sales->can('viewAny', Opportunity::class));
        $this->assertTrue($sales->can('view', $mine));
        $this->assertTrue($sales->can('process', $mine));
        $this->assertTrue($sales->can('createQuotation', $mine));

        $this->assertFalse($sales->can('view', $other));
        $this->assertFalse($sales->can('process', $other));
        $this->assertFalse($sales->can('createQuotation', $other));
    }

    public function test_sales_manager_can_process_any_opportunity(): void
    {
        [$manager] = $this->makeV1SalesManager();
        $opportunity = Opportunity::factory()->create();

        $this->assertTrue($manager->can('view', $opportunity));
        $this->assertTrue($manager->can('process', $opportunity));
        $this->assertTrue($manager->can('createQuotation', $opportunity));
    }

    public function test_system_admin_and_cross_business_readers_can_read_but_not_process(): void
    {
        $opportunity = Opportunity::factory()->create();

        foreach ([$this->makeV1SystemAdmin(), $this->makeV1Executive(), $this->makeV1Viewer()] as $actor) {
            $this->assertTrue($actor->can('viewAny', Opportunity::class));
            $this->assertTrue($actor->can('view', $opportunity));
            $this->assertFalse($actor->can('process', $opportunity));
            $this->assertFalse($actor->can('createQuotation', $opportunity));
        }
    }

    public function test_finance_customer_care_and_pure_marketing_cannot_open_pre_sales_opportunity(): void
    {
        $opportunity = Opportunity::factory()->create();
        $actors = [
            $this->makeV1Finance()[0],
            $this->makeV1CustomerCareStaff()[0],
            $this->makeV1MarketingStaff()[0],
        ];

        foreach ($actors as $actor) {
            $this->assertFalse($actor->can('viewAny', Opportunity::class));
            $this->assertFalse($actor->can('view', $opportunity));
        }
    }
}
