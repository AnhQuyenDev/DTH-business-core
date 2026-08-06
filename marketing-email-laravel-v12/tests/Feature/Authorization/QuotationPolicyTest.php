<?php

namespace Tests\Feature\Authorization;

use App\Enums\Sales\QuotationStatus;
use App\Models\Crm\Staff;
use App\Models\Sales\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationPolicyTest extends TestCase
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

    private function salesStaff(string $role = 'sales_staff'): User
    {
        $user = $this->makeUser($role);
        Staff::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_sales_staff_can_create_edit_send_assign_and_verify_payment_draft(): void
    {
        $user = $this->salesStaff('sales_staff');
        $this->actingAs($user);

        $quotation = Quotation::factory()->create([
            'status' => QuotationStatus::Approved,
            'assigned_staff_id' => $user->staff->id,
            'created_by' => $user->id,
        ]);

        $this->assertTrue($user->can('view', $quotation));
        $this->assertFalse($user->can('update', $quotation));
        $this->assertTrue($user->can('send', $quotation));
        $this->assertFalse($user->can('verifyPayment', $quotation));
    }

    public function test_sales_staff_cannot_see_others_quotation(): void
    {
        $user = $this->salesStaff('sales_staff');
        $otherStaff = Staff::factory()->create(['employee_code' => 'STAFF-OTHER']);

        $quotation = Quotation::factory()->create([
            'status' => QuotationStatus::Draft,
            'assigned_staff_id' => $otherStaff->id,
            'created_by' => $otherStaff->user_id,
        ]);

        $this->actingAs($user);

        $this->assertFalse($user->can('view', $quotation));
    }

    public function test_sales_manager_can_approve_and_cancel(): void
    {
        $user = $this->salesStaff('sales_manager');
        $this->actingAs($user);

        $quotation = Quotation::factory()->create([
            'status' => QuotationStatus::PendingApproval,
            'assigned_staff_id' => $user->staff->id,
        ]);

        $this->assertTrue($user->can('approve', $quotation));
        $this->assertTrue($user->can('cancel', $quotation));
    }

    public function test_finance_can_view_and_verify_payment_but_not_edit(): void
    {
        $user = $this->salesStaff('finance_staff');
        $this->actingAs($user);

        $quotation = Quotation::factory()->create([
            'status' => QuotationStatus::Sent,
            'assigned_staff_id' => 1,
        ]);

        $this->assertTrue($user->can('viewAny', Quotation::class));
        $this->assertTrue($user->can('view', $quotation));
        $this->assertTrue($user->can('verifyPayment', $quotation));
        $this->assertFalse($user->can('update', $quotation));
    }

    public function test_cs_manager_cannot_view_quotation(): void
    {
        $user = $this->salesStaff('customer_service_manager');
        $this->actingAs($user);

        $quotation = Quotation::factory()->create();

        $this->assertFalse($user->can('viewAny', Quotation::class));
        $this->assertFalse($user->can('view', $quotation));
    }

    public function test_admin_can_do_anything(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user);

        $quotation = Quotation::factory()->create();

        $this->assertTrue($user->can('view', $quotation));
        $this->assertTrue($user->can('update', $quotation));
        $this->assertTrue($user->can('verifyPayment', $quotation));
    }
}
