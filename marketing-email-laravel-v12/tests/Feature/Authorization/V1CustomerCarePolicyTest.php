<?php

namespace Tests\Feature\Authorization;

use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerAssignmentType;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\CustomerInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class V1CustomerCarePolicyTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    private function customer(): Customer
    {
        return Customer::factory()->create([
            'customer_code' => 'CUS-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
        ]);
    }

    private function assign(Customer $customer, int $staffId): CustomerAssignment
    {
        return CustomerAssignment::factory()->create([
            'customer_id' => $customer->id,
            'staff_id' => $staffId,
            'assignment_type' => CustomerAssignmentType::Owner->value,
            'status' => CustomerAssignmentStatus::Active->value,
            'starts_at' => now(),
        ]);
    }

    public function test_customer_care_manager_can_view_interact_and_manage_assignments_for_all_customers(): void
    {
        [$manager] = $this->makeV1CustomerCareManager();
        $customer = $this->customer();

        $this->assertTrue($manager->can('viewAny', Customer::class));
        $this->assertTrue($manager->can('view', $customer));
        $this->assertTrue($manager->can('interact', $customer));
        $this->assertTrue($manager->can('manageAssignments', $customer));
        $this->assertTrue($manager->can('create', CustomerAssignment::class));
        $this->assertTrue($manager->can('create', CustomerInteraction::class));
    }

    public function test_customer_care_staff_can_only_view_and_interact_with_active_assignment(): void
    {
        [$support, $staff] = $this->makeV1CustomerCareStaff();
        $mine = $this->customer();
        $other = $this->customer();
        $this->assign($mine, $staff->id);

        $this->assertTrue($support->can('viewAny', Customer::class));
        $this->assertTrue($support->can('view', $mine));
        $this->assertTrue($support->can('interact', $mine));
        $this->assertFalse($support->can('manageAssignments', $mine));

        $this->assertFalse($support->can('view', $other));
        $this->assertFalse($support->can('interact', $other));
    }

    public function test_system_admin_is_customer_care_read_only(): void
    {
        $admin = $this->makeV1SystemAdmin();
        $customer = $this->customer();

        $this->assertTrue($admin->can('viewAny', Customer::class));
        $this->assertTrue($admin->can('view', $customer));
        $this->assertFalse($admin->can('interact', $customer));
        $this->assertFalse($admin->can('manageAssignments', $customer));
        $this->assertFalse($admin->can('create', CustomerInteraction::class));
        $this->assertFalse($admin->can('create', CustomerAssignment::class));
    }

    public function test_cross_business_reader_can_read_customer_but_cannot_interact(): void
    {
        $customer = $this->customer();

        foreach ([$this->makeV1Executive(), $this->makeV1Viewer()] as $reader) {
            $this->assertTrue($reader->can('viewAny', Customer::class));
            $this->assertTrue($reader->can('view', $customer));
            $this->assertFalse($reader->can('interact', $customer));
        }
    }

    public function test_sales_marketing_and_finance_do_not_gain_post_sale_customer_care_access(): void
    {
        $customer = $this->customer();
        $actors = [
            $this->makeV1SalesStaff()[0],
            $this->makeV1MarketingStaff()[0],
            $this->makeV1Finance()[0],
        ];

        foreach ($actors as $actor) {
            $this->assertFalse($actor->can('viewAny', Customer::class));
            $this->assertFalse($actor->can('view', $customer));
            $this->assertFalse($actor->can('interact', $customer));
        }
    }
}
