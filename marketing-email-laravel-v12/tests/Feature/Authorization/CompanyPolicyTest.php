<?php

namespace Tests\Feature\Authorization;

use App\Models\Crm\Company;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Sales\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPolicyTest extends TestCase
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

    private function makeCompany(?Staff $accountOwner = null): Company
    {
        return Company::query()->create([
            'company_code' => 'COM-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'legal_name' => 'Công ty ABC',
            'account_owner_staff_id' => $accountOwner?->id,
        ]);
    }

    private function makeStaff(User $user): Staff
    {
        return Staff::factory()->create(['user_id' => $user->id]);
    }

    public function test_managers_and_admins_can_view_any_company(): void
    {
        foreach (['admin', 'customer_service_manager', 'sales_manager', 'marketing_manager'] as $role) {
            $user = $this->makeUser($role);
            $this->actingAs($user);

            $this->assertTrue(
                $user->can('viewAny', Company::class),
                "Role {$role} should view any company"
            );
        }
    }

    public function test_cs_staff_sees_company_as_account_owner(): void
    {
        $user = $this->makeUser('customer_service_staff');
        $staff = $this->makeStaff($user);
        $company = $this->makeCompany($staff);

        $this->actingAs($user);

        $this->assertTrue($user->can('view', $company));
    }

    public function test_cs_staff_sees_company_with_assigned_lead(): void
    {
        $user = $this->makeUser('customer_service_staff');
        $staff = $this->makeStaff($user);
        $company = $this->makeCompany();

        Lead::factory()->create([
            'company_id' => $company->id,
            'assigned_staff_id' => $staff->id,
        ]);

        $this->actingAs($user);

        $this->assertTrue($user->can('view', $company));
    }

    public function test_cs_staff_cannot_view_company_of_other_lead(): void
    {
        $user = $this->makeUser('customer_service_staff');
        $this->makeStaff($user);
        $company = $this->makeCompany();

        $this->actingAs($user);

        $this->assertFalse($user->can('view', $company));
    }

    public function test_sales_staff_sees_company_with_assigned_opportunity(): void
    {
        $user = $this->makeUser('sales_staff');
        $staff = $this->makeStaff($user);
        $company = $this->makeCompany();

        Opportunity::factory()->create([
            'company_id' => $company->id,
            'assigned_staff_id' => $staff->id,
        ]);

        $this->actingAs($user);

        $this->assertTrue($user->can('view', $company));
    }

    public function test_sales_staff_cannot_view_unrelated_company(): void
    {
        $user = $this->makeUser('sales_staff');
        $this->makeStaff($user);
        $company = $this->makeCompany();

        $this->actingAs($user);

        $this->assertFalse($user->can('view', $company));
    }

    public function test_marketing_staff_cannot_view_company(): void
    {
        $user = $this->makeUser('marketing_staff');
        $company = $this->makeCompany();

        $this->actingAs($user);

        $this->assertFalse($user->can('viewAny', Company::class));
        $this->assertFalse($user->can('view', $company));
    }

    public function test_no_role_can_create_company(): void
    {
        $csManager = $this->makeUser('customer_service_manager');
        $this->actingAs($csManager);

        $this->assertFalse($csManager->can('create', Company::class));

        $salesManager = $this->makeUser('sales_manager');
        $this->actingAs($salesManager);

        $this->assertFalse($salesManager->can('create', Company::class));
    }
}
