<?php

namespace Tests\Feature\Authorization;

use App\Models\Crm\Company;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadPolicyTest extends TestCase
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

    public function test_marketing_staff_can_view_lead_but_not_process(): void
    {
        $user = $this->makeUser('marketing_staff');
        $this->actingAs($user);

        $lead = Lead::factory()->create();

        $this->assertTrue($user->can('viewAny', Lead::class));
        $this->assertTrue($user->can('view', $lead));
        $this->assertFalse($user->can('process', $lead));
    }

    public function test_cs_staff_can_view_and_process_assigned_lead(): void
    {
        $user = $this->makeUser('customer_service_staff');
        $staff = Staff::factory()->create(['user_id' => $user->id]);

        $lead = Lead::factory()->create([
            'assigned_staff_id' => $staff->id,
        ]);

        $this->actingAs($user);

        $this->assertTrue($user->can('view', $lead));
        $this->assertTrue($user->can('process', $lead));
    }

    public function test_account_owner_can_view_lead_but_not_process_when_lead_assigned_elsewhere(): void
    {
        $ownerUser = $this->makeUser('customer_service_staff');
        $ownerStaff = Staff::factory()->create(['user_id' => $ownerUser->id]);

        $otherStaff = Staff::factory()->create([
            'employee_code' => 'STAFF-OTHER',
        ]);

        $company = Company::query()->create([
            'company_code' => 'COM-OWN-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'legal_name' => 'Công ty Chủ Sở Hữu',
            'account_owner_staff_id' => $ownerStaff->id,
        ]);

        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'assigned_staff_id' => $otherStaff->id,
        ]);

        $this->actingAs($ownerUser);

        $this->assertTrue($ownerUser->can('view', $lead));
        $this->assertFalse($ownerUser->can('process', $lead));
    }

    public function test_cs_staff_cannot_open_unrelated_lead(): void
    {
        $user = $this->makeUser('customer_service_staff');
        Staff::factory()->create(['user_id' => $user->id]);

        $otherStaff = Staff::factory()->create([
            'employee_code' => 'STAFF-OTHER2',
        ]);

        $lead = Lead::factory()->create([
            'assigned_staff_id' => $otherStaff->id,
        ]);

        $this->actingAs($user);

        $this->assertFalse($user->can('view', $lead));
    }

    public function test_cs_manager_can_process_assign_and_archive_all_leads(): void
    {
        $user = $this->makeUser('customer_service_manager');
        $this->actingAs($user);

        $lead = Lead::factory()->create();

        $this->assertTrue($user->can('process', $lead));
        $this->assertTrue($user->can('assign', $lead));
        $this->assertTrue($user->can('reassign', $lead));
        $this->assertTrue($user->can('archive', $lead));
    }

    public function test_sales_staff_cannot_access_leads(): void
    {
        $user = $this->makeUser('sales_staff');
        $this->actingAs($user);

        $lead = Lead::factory()->create();

        $this->assertFalse($user->can('viewAny', Lead::class));
        $this->assertFalse($user->can('view', $lead));
    }

    public function test_admin_can_do_anything(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user);

        $lead = Lead::factory()->create();

        $this->assertTrue($user->can('view', $lead));
        $this->assertTrue($user->can('process', $lead));
    }
}
