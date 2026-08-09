<?php

namespace Tests\Feature\Authorization;

use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerAssignmentType;
use App\Filament\Pages\CustomerCarePage;
use App\Filament\Resources\CustomerResource;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\CustomerInteraction;
use App\Models\Crm\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCareAccessTest extends TestCase
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
        return Staff::factory()->create(['user_id' => $user->id]);
    }

    private function makeCustomer(): Customer
    {
        return Customer::factory()->create([
            'customer_code' => 'CUS-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
        ]);
    }

    private function assignCustomer(Customer $customer, Staff $staff): CustomerAssignment
    {
        return CustomerAssignment::factory()->create([
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'assignment_type' => CustomerAssignmentType::Owner,
            'status' => CustomerAssignmentStatus::Active,
            'starts_at' => now(),
        ]);
    }

    public function test_cs_manager_can_view_all_customers(): void
    {
        $this->actingAs($this->makeUser('customer_service_manager'));

        $customer = $this->makeCustomer();

        $this->assertTrue(auth()->user()->can('viewAny', Customer::class));
        $this->assertTrue(auth()->user()->can('view', $customer));
        $this->assertTrue(CustomerResource::canViewAny());
        $this->assertTrue(CustomerCarePage::canAccess());
    }

    public function test_cs_staff_sees_customer_with_active_assignment(): void
    {
        $user = $this->makeUser('customer_service_staff');
        $staff = $this->makeStaff($user);

        $customer = $this->makeCustomer();
        $this->assignCustomer($customer, $staff);

        $this->actingAs($user);

        $this->assertTrue(auth()->user()->can('view', $customer));
    }

    public function test_cs_staff_cannot_open_other_customer_url(): void
    {
        $user = $this->makeUser('customer_service_staff');
        $this->makeStaff($user);

        $otherCustomer = $this->makeCustomer();

        $this->actingAs($user);

        $this->assertFalse(auth()->user()->can('view', $otherCustomer));
    }

    public function test_sales_finance_marketing_cannot_access_customer_care(): void
    {
        foreach (['sales_staff', 'sales_manager', 'finance_staff', 'marketing_manager', 'marketing_staff', 'viewer'] as $role) {
            $user = $this->makeUser($role);
            $this->actingAs($user);

            $this->assertFalse(CustomerCarePage::canAccess(), "Role {$role} should not access Customer Care");
            $this->assertFalse(CustomerResource::canViewAny(), "Role {$role} should not view customers");
        }
    }

    public function test_admin_customer_care_is_read_only(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $customer = $this->makeCustomer();

        $this->assertTrue(auth()->user()->can('viewAny', Customer::class));
        $this->assertTrue(auth()->user()->can('view', $customer));
        $this->assertTrue(CustomerCarePage::canAccess());
        $this->assertFalse(auth()->user()->can('update', $customer));
        $this->assertFalse(auth()->user()->can('interact', $customer));
        $this->assertFalse(auth()->user()->can('manageAssignments', $customer));
        $this->assertFalse(auth()->user()->can('create', CustomerInteraction::class));
        $this->assertFalse(auth()->user()->can('create', CustomerAssignment::class));
    }

    public function test_cs_staff_can_interact_assigned_customer(): void
    {
        $user = $this->makeUser('customer_service_staff');
        $staff = $this->makeStaff($user);

        $customer = $this->makeCustomer();
        $this->assignCustomer($customer, $staff);

        $this->actingAs($user);

        $this->assertTrue(auth()->user()->can('interact', $customer));
    }

    public function test_cs_staff_cannot_interact_unassigned_customer(): void
    {
        $user = $this->makeUser('customer_service_staff');
        $this->makeStaff($user);

        $customer = $this->makeCustomer();

        $this->actingAs($user);

        $this->assertFalse(auth()->user()->can('interact', $customer));
    }
}
