<?php

namespace Tests\Feature\Authorization;

use App\Models\Crm\Company;
use App\Models\Crm\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class V1LeadPolicyTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    public function test_sales_staff_processes_only_assigned_lead(): void
    {
        [$sales, $staff] = $this->makeV1SalesStaff();
        [, $otherStaff] = $this->makeV1SalesStaff('Other Sales');
        $mine = Lead::factory()->create(['assigned_staff_id' => $staff->id]);
        $other = Lead::factory()->create(['assigned_staff_id' => $otherStaff->id]);

        $this->assertTrue($sales->can('viewAny', Lead::class));
        $this->assertTrue($sales->can('view', $mine));
        $this->assertTrue($sales->can('process', $mine));
        $this->assertTrue($sales->can('update', $mine));
        $this->assertTrue($sales->can('createOpportunity', $mine));

        $this->assertFalse($sales->can('view', $other));
        $this->assertFalse($sales->can('process', $other));
        $this->assertFalse($sales->can('createOpportunity', $other));
    }

    public function test_account_owner_can_read_company_lead_but_cannot_process_if_assigned_elsewhere(): void
    {
        [$owner, $ownerStaff] = $this->makeV1SalesStaff('Account Owner');
        [, $handlerStaff] = $this->makeV1SalesStaff('Lead Handler');
        $company = Company::query()->create([
            'company_code' => 'COM-'.fake()->unique()->numberBetween(100000, 999999),
            'legal_name' => 'Công ty Lead Owner',
            'account_owner_staff_id' => $ownerStaff->id,
        ]);
        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'assigned_staff_id' => $handlerStaff->id,
        ]);

        $this->assertTrue($owner->can('view', $lead));
        $this->assertFalse($owner->can('process', $lead));
    }

    public function test_sales_manager_can_view_process_assign_reassign_and_archive_all_leads(): void
    {
        [$manager] = $this->makeV1SalesManager();
        $lead = Lead::factory()->create();

        $this->assertTrue($manager->can('view', $lead));
        $this->assertTrue($manager->can('process', $lead));
        $this->assertTrue($manager->can('assign', $lead));
        $this->assertTrue($manager->can('reassign', $lead));
        $this->assertTrue($manager->can('archive', $lead));
    }

    public function test_system_admin_is_read_only_for_lead_workflow(): void
    {
        $admin = $this->makeV1SystemAdmin();
        $lead = Lead::factory()->create();

        $this->assertTrue($admin->can('viewAny', Lead::class));
        $this->assertTrue($admin->can('view', $lead));
        $this->assertFalse($admin->can('process', $lead));
        $this->assertFalse($admin->can('assign', $lead));
        $this->assertFalse($admin->can('createOpportunity', $lead));
    }

    public function test_pure_marketing_customer_care_and_finance_cannot_operate_leads(): void
    {
        $lead = Lead::factory()->create();
        $actors = [
            $this->makeV1MarketingStaff()[0],
            $this->makeV1CustomerCareStaff()[0],
            $this->makeV1Finance()[0],
        ];

        foreach ($actors as $actor) {
            $this->assertFalse($actor->can('viewAny', Lead::class));
            $this->assertFalse($actor->can('view', $lead));
            $this->assertFalse($actor->can('process', $lead));
        }
    }
}
