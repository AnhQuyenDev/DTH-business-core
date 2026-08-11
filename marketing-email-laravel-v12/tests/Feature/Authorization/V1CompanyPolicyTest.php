<?php

namespace Tests\Feature\Authorization;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Models\Crm\Company;
use App\Models\Crm\Lead;
use App\Models\Sales\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class V1CompanyPolicyTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    private function company(?int $ownerStaffId = null): Company
    {
        return Company::query()->create([
            'company_code' => 'COM-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'legal_name' => 'Công ty '.fake()->unique()->company(),
            'account_owner_staff_id' => $ownerStaffId,
            'lifecycle_stage' => CompanyLifecycleStage::Prospect->value,
        ]);
    }

    public function test_system_admin_and_cross_business_reader_can_read_company_but_not_operate_it(): void
    {
        $company = $this->company();

        foreach ([$this->makeV1SystemAdmin(), $this->makeV1Executive(), $this->makeV1Viewer()] as $user) {
            $this->assertTrue($user->can('viewAny', Company::class));
            $this->assertTrue($user->can('view', $company));
            $this->assertFalse($user->can('create', Company::class));
            $this->assertFalse($user->can('update', $company));
        }
    }

    public function test_sales_manager_can_read_all_companies_and_manage_owner(): void
    {
        [$manager] = $this->makeV1SalesManager();
        $company = $this->company();

        $this->assertTrue($manager->can('viewAny', Company::class));
        $this->assertTrue($manager->can('view', $company));
        $this->assertTrue($manager->can('manageOwner', $company));
        $this->assertFalse($manager->can('create', Company::class));
    }

    public function test_sales_staff_can_read_owned_or_related_company_only(): void
    {
        [$sales, $staff] = $this->makeV1SalesStaff();

        $owned = $this->company($staff->id);
        $withLead = $this->company();
        Lead::factory()->create(['company_id' => $withLead->id, 'assigned_staff_id' => $staff->id]);
        $withOpportunity = $this->company();
        Opportunity::factory()->create(['company_id' => $withOpportunity->id, 'assigned_staff_id' => $staff->id]);
        $unrelated = $this->company();

        $this->assertTrue($sales->can('viewAny', Company::class));
        $this->assertTrue($sales->can('view', $owned));
        $this->assertTrue($sales->can('view', $withLead));
        $this->assertTrue($sales->can('view', $withOpportunity));
        $this->assertFalse($sales->can('view', $unrelated));
        $this->assertFalse($sales->can('manageOwner', $owned));
    }

    public function test_pure_marketing_customer_care_and_finance_do_not_gain_pre_sales_company_access(): void
    {
        $company = $this->company();
        $actors = [
            $this->makeV1MarketingStaff()[0],
            $this->makeV1CustomerCareStaff()[0],
            $this->makeV1Finance()[0],
        ];

        foreach ($actors as $actor) {
            $this->assertFalse($actor->can('viewAny', Company::class));
            $this->assertFalse($actor->can('view', $company));
        }
    }

    public function test_super_admin_bypasses_company_policy_for_demo_and_recovery(): void
    {
        $super = $this->makeV1SuperAdmin();
        $company = $this->company();

        $this->assertTrue($super->can('view', $company));
        $this->assertTrue($super->can('create', Company::class));
        $this->assertTrue($super->can('update', $company));
        $this->assertTrue($super->can('delete', $company));
    }
}
