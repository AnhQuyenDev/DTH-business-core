<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Sales\PaymentStatus;
use App\Models\Crm\Customer;
use App\Services\Sales\QuotationPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Sales\Concerns\PaymentConversionSetup;
use Tests\TestCase;

class PaidQuotationConvertsCustomerTest extends TestCase
{
    use PaymentConversionSetup;
    use RefreshDatabase;

    public function test_paid_opportunity_quotation_creates_customer_and_wins_opportunity(): void
    {
        Mail::fake();

        $flow = $this->buildFlow();
        $finance = $this->makeV1Finance()[0];
        $this->preparePendingPaymentNotice($flow['quotation']);

        $service = app(QuotationPaymentService::class);
        $service->updateStatus(
            quotation: $flow['quotation'],
            newStatus: PaymentStatus::Paid,
            user: $finance,
            note: 'Đã đối soát giao dịch ngân hàng ngày 06/08/2026.',
        );

        $customer = Customer::query()->where(
            'converted_from_opportunity_id',
            $flow['opportunity']->id
        )->firstOrFail();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_id' => $flow['company']->id,
            'converted_from_opportunity_id' => $flow['opportunity']->id,
            'customer_type' => 'business',
            'status' => 'active',
            'conversion_reason' => 'purchased',
            'consent_status' => CustomerConsentStatus::Pending->value,
            'lifecycle_stage' => 'new_customer',
        ]);

        $this->assertDatabaseHas('quotations', [
            'id' => $flow['quotation']->id,
            'payment_status' => 'paid',
            'customer_id' => $customer->id,
        ]);

        $paid = $flow['quotation']->fresh();
        $this->assertNotNull($paid->paid_at);
        $this->assertSame($finance->id, $paid->payment_verified_by_user_id);
        $this->assertSame('Đã đối soát giao dịch ngân hàng ngày 06/08/2026.', $paid->payment_note);

        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $flow['opportunity']->id,
            'stage' => 'won',
        ]);

        $this->assertDatabaseHas('contact_qualifications', [
            'id' => $flow['qualification']->id,
            'status' => 'converted',
            'qualification_result' => 'purchased',
            'converted_customer_id' => $customer->id,
        ]);

        $this->assertDatabaseHas('companies', [
            'id' => $flow['company']->id,
            'lifecycle_stage' => 'customer',
        ]);
    }

    public function test_paid_personal_opportunity_creates_personal_customer(): void
    {
        Mail::fake();

        $flow = $this->buildFlow(withCompany: false);
        $finance = $this->makeV1Finance()[0];
        $this->preparePendingPaymentNotice($flow['quotation']);

        app(QuotationPaymentService::class)->updateStatus(
            quotation: $flow['quotation'],
            newStatus: PaymentStatus::Paid,
            user: $finance,
            note: 'Đã xác minh chuyển khoản.',
        );

        $customer = Customer::query()->where(
            'converted_from_opportunity_id',
            $flow['opportunity']->id
        )->firstOrFail();

        $this->assertSame('personal', $customer->customer_type);
        $this->assertNull($customer->company_id);
        $this->assertSame($flow['contact']->id, $customer->contact_id);

        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $flow['opportunity']->id,
            'stage' => 'won',
        ]);
    }

    public function test_customer_assignment_uses_account_owner_when_present(): void
    {
        Mail::fake();

        $accountOwner = $this->makeStaff();
        $opportunityOwner = $this->makeStaff();
        $flow = $this->buildFlow(
            withCompany: true,
            accountOwner: $accountOwner,
            opportunityOwner: $opportunityOwner,
        );
        $finance = $this->makeV1Finance()[0];
        $this->preparePendingPaymentNotice($flow['quotation']);

        app(QuotationPaymentService::class)->updateStatus(
            quotation: $flow['quotation'],
            newStatus: PaymentStatus::Paid,
            user: $finance,
            note: 'Xác minh.',
        );

        $customer = Customer::query()->where(
            'converted_from_opportunity_id',
            $flow['opportunity']->id
        )->firstOrFail();

        $this->assertDatabaseHas('customer_assignments', [
            'customer_id' => $customer->id,
            'staff_id' => $accountOwner->id,
            'assignment_type' => 'owner',
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('customer_assignments', [
            'customer_id' => $customer->id,
            'staff_id' => $opportunityOwner->id,
            'assignment_type' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_customer_assignment_falls_back_to_opportunity_owner(): void
    {
        Mail::fake();

        $opportunityOwner = $this->makeStaff();
        $flow = $this->buildFlow(
            withCompany: true,
            accountOwner: null,
            opportunityOwner: $opportunityOwner,
        );
        $finance = $this->makeV1Finance()[0];
        $this->preparePendingPaymentNotice($flow['quotation']);

        app(QuotationPaymentService::class)->updateStatus(
            quotation: $flow['quotation'],
            newStatus: PaymentStatus::Paid,
            user: $finance,
            note: 'Xác minh.',
        );

        $customer = Customer::query()->where(
            'converted_from_opportunity_id',
            $flow['opportunity']->id
        )->firstOrFail();

        $this->assertDatabaseHas('customer_assignments', [
            'customer_id' => $customer->id,
            'staff_id' => $opportunityOwner->id,
            'assignment_type' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_conversion_creates_system_interaction(): void
    {
        Mail::fake();

        $flow = $this->buildFlow();
        $finance = $this->makeV1Finance()[0];
        $this->preparePendingPaymentNotice($flow['quotation']);

        app(QuotationPaymentService::class)->updateStatus(
            quotation: $flow['quotation'],
            newStatus: PaymentStatus::Paid,
            user: $finance,
            note: 'Xác minh.',
        );

        $customer = Customer::query()->where(
            'converted_from_opportunity_id',
            $flow['opportunity']->id
        )->firstOrFail();

        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $customer->id,
            'interaction_type' => 'system',
            'subject' => 'Chuyển thành khách hàng từ thanh toán',
            'outcome' => 'paid_conversion',
            'status' => 'completed',
        ]);
    }
}
