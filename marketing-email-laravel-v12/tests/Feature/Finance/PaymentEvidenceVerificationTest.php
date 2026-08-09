<?php

namespace Tests\Feature\Finance;

use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Services\Sales\QuotationPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Sales\Concerns\PaymentConversionSetup;
use Tests\TestCase;

class PaymentEvidenceVerificationTest extends TestCase
{
    use PaymentConversionSetup;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('business_flow.v2_enabled', true);
        config()->set('business_flow.customer_on_paid_only', true);
    }

    public function test_finance_cannot_mark_paid_when_pending_notice_has_no_evidence(): void
    {
        $flow = $this->buildFlow();
        $finance = $this->makeUser('finance_staff');

        $quotation = $flow['quotation'];
        $quotation->update([
            'payment_status' => PaymentStatus::PendingVerification->value,
        ]);

        $quotation->paymentNotices()->create([
            'status' => PaymentNoticeStatus::Pending->value,
            'payer_name' => 'Nguyễn Minh Khoa',
            'payer_email' => $quotation->party_email,
            'declared_amount' => $quotation->grand_total,
            'submitted_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(QuotationPaymentService::class)->updateStatus(
            $quotation->fresh(),
            PaymentStatus::Paid,
            $finance,
            'Đã đối soát',
        );
    }
}
