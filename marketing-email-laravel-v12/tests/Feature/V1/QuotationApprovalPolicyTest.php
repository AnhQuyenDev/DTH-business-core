<?php

namespace Tests\Feature\V1;

use App\Enums\Sales\ApprovalStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\CompanySetting;
use App\Models\Crm\Staff;
use App\Models\Sales\BankAccount;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Sales\QuotationApprovalService;
use Database\Seeders\V1AcceptanceTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationApprovalPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(V1AcceptanceTestSeeder::class);
    }

    private function quotation(float $amount): array
    {
        $user = User::query()->where('email', 'commercial.v1@dth.local')->firstOrFail();
        $staff = Staff::query()->where('user_id', $user->id)->firstOrFail();

        $opportunity = Opportunity::factory()->create([
            'assigned_staff_id' => $staff->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'stage' => 'qualified',
        ]);

        $bank = BankAccount::query()->create([
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => '0011223344',
            'account_name' => 'DTH TEST',
            'status' => 'active',
        ]);

        $quotation = Quotation::factory()
            ->forOpportunity($opportunity)
            ->create([
                'assigned_staff_id' => $staff->id,
                'bank_account_id' => $bank->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'subtotal' => $amount,
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => $amount,
                'status' => QuotationStatus::Draft,
                'customer_id' => null,
                'customer_snapshot' => [
                    'display_name' => 'Khách V1 chưa chuyển đổi',
                    'contact_name' => 'Nguyễn Buyer',
                    'email' => 'buyer@example.test',
                    'customer_type' => 'personal',
                ],
            ]);

        $quotation->items()->create([
            'service_code_snapshot' => 'SV-V1',
            'service_name_snapshot' => 'Dịch vụ V1',
            'package_name_snapshot' => 'Gói V1',
            'item_type' => 'package',
            'quantity' => 1,
            'unit_price' => $amount,
            'line_subtotal' => $amount,
            'discount_amount' => 0,
            'vat_amount' => 0,
            'line_total' => $amount,
        ]);

        return [$quotation, $user];
    }

    public function test_quotation_inside_threshold_is_auto_approved_by_policy_without_creating_customer(): void
    {
        CompanySetting::firstOrCreateDefault()->update([
            'quotation_approval_mode' => 'amount_threshold',
            'quotation_approval_amount_threshold' => 20_000_000,
        ]);

        [$quotation, $user] = $this->quotation(5_000_000);
        $result = app(QuotationApprovalService::class)->submitForApproval($quotation, $user);

        $this->assertSame(QuotationStatus::Approved, $result->status);
        $this->assertNull($result->customer_id);
        $this->assertNull($result->approved_by);
        $this->assertTrue(
            $result->approvals()
                ->where('approver_role', 'approval_policy')
                ->where('status', ApprovalStatus::Approved->value)
                ->exists()
        );
    }

    public function test_quotation_above_threshold_waits_for_sales_manager(): void
    {
        CompanySetting::firstOrCreateDefault()->update([
            'quotation_approval_mode' => 'amount_threshold',
            'quotation_approval_amount_threshold' => 20_000_000,
        ]);

        [$quotation, $user] = $this->quotation(25_000_000);
        $result = app(QuotationApprovalService::class)->submitForApproval($quotation, $user);

        $this->assertSame(QuotationStatus::PendingApproval, $result->status);
        $this->assertNull($result->customer_id);
        $this->assertTrue(
            $result->approvals()
                ->where('approver_role', 'sales_department_manager')
                ->where('status', ApprovalStatus::Pending->value)
                ->exists()
        );
    }
}
