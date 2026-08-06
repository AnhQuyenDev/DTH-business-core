<?php

namespace Tests\Feature\Sales;

use App\Enums\Sales\PaymentStatus;
use App\Models\Crm\Customer;
use App\Services\Sales\QuotationPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Sales\Concerns\PaymentConversionSetup;
use Tests\TestCase;

class PaymentConversionIdempotencyTest extends TestCase
{
    use PaymentConversionSetup;
    use RefreshDatabase;

    public function test_retrying_paid_payment_does_not_duplicate_customer_assignment_or_revenue(): void
    {
        Mail::fake();

        $flow = $this->buildFlow();
        $service = app(QuotationPaymentService::class);

        $service->updateStatus(
            quotation: $flow['quotation'],
            newStatus: PaymentStatus::Paid,
            user: $flow['admin'],
            note: 'Lần 1',
        );

        $first = Customer::query()->where(
            'converted_from_opportunity_id',
            $flow['opportunity']->id
        )->firstOrFail();

        $service->updateStatus(
            quotation: $flow['quotation']->fresh(),
            newStatus: PaymentStatus::Paid,
            user: $flow['admin'],
            note: 'Retry',
        );

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('customer_assignments', 1);

        $this->assertSame(1, Customer::query()->where(
            'converted_from_opportunity_id',
            $flow['opportunity']->id
        )->count());

        $interactionCount = $first->interactions()
            ->where('interaction_type', 'system')
            ->where('subject', 'Chuyển thành khách hàng từ thanh toán')
            ->count();
        $this->assertSame(1, $interactionCount);

        $fresh = $first->fresh();
        $this->assertSame(
            (float) $flow['quotation']->grand_total,
            (float) $fresh->total_revenue
        );
    }
}
