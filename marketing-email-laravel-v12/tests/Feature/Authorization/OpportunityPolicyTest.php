<?php

namespace Tests\Feature\Authorization;

use App\Enums\Sales\OpportunityStage;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityPolicyTest extends TestCase
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

    private function makeStaff(User $user): Staff
    {
        return Staff::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-'.fake()->unique()->numberBetween(1000, 9999),
            'full_name' => 'Staff '.$user->name,
            'employment_status' => 'active',
            'can_receive_customers' => true,
        ]);
    }

    private function makeOpportunity(?Staff $assignedStaff = null): Opportunity
    {
        $contact = Contact::factory()->create();

        return Opportunity::query()->create([
            'opportunity_code' => 'OPP-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'primary_contact_id' => $contact->id,
            'assigned_staff_id' => $assignedStaff?->id,
            'title' => 'Cơ hội test',
            'stage' => OpportunityStage::Qualified->value,
        ]);
    }

    public function test_cs_manager_can_view_and_create_opportunity(): void
    {
        $user = $this->makeUser('customer_service_manager');
        $this->actingAs($user);

        $this->assertTrue(auth()->user()->can('viewAny', Opportunity::class));
        $this->assertTrue(auth()->user()->can('create', Opportunity::class));
    }

    public function test_cs_manager_cannot_process_stage(): void
    {
        $user = $this->makeUser('customer_service_manager');
        $this->actingAs($user);

        $opportunity = $this->makeOpportunity();

        $this->assertFalse(auth()->user()->can('process', $opportunity));
    }

    public function test_sales_manager_can_process_all_opportunities(): void
    {
        $user = $this->makeUser('sales_manager');
        $this->actingAs($user);

        $opportunity = $this->makeOpportunity(Staff::factory()->create());

        $this->assertTrue(auth()->user()->can('view', $opportunity));
        $this->assertTrue(auth()->user()->can('process', $opportunity));
    }

    public function test_sales_staff_only_processes_assigned_opportunity(): void
    {
        $user = $this->makeUser('sales_staff');
        $staff = $this->makeStaff($user);

        $own = $this->makeOpportunity($staff);
        $other = $this->makeOpportunity(Staff::factory()->create());

        $this->actingAs($user);

        $this->assertTrue(auth()->user()->can('view', $own));
        $this->assertTrue(auth()->user()->can('process', $own));
        $this->assertFalse(auth()->user()->can('view', $other));
        $this->assertFalse(auth()->user()->can('process', $other));
    }

    public function test_sales_staff_cannot_open_other_staff_opportunity_by_policy(): void
    {
        $user = $this->makeUser('sales_staff');
        $this->makeStaff($user);

        $other = $this->makeOpportunity(Staff::factory()->create());

        $this->actingAs($user);

        $this->assertFalse(auth()->user()->can('view', $other));
    }

    public function test_finance_cannot_view_opportunity(): void
    {
        $user = $this->makeUser('finance_staff');
        $this->actingAs($user);

        $this->assertFalse(auth()->user()->can('viewAny', Opportunity::class));

        $opportunity = $this->makeOpportunity();
        $this->assertFalse(auth()->user()->can('view', $opportunity));
    }
}
