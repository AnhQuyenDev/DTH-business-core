<?php

namespace Tests\Feature\Finance;

use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Models\CompanySetting;
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

    public function test_v1_default_allows_finance_to_verify_payment_without_uploaded_evidence(): void
    {
        CompanySetting::firstOrCreateDefault()->update(['payment_evidence_required' => false]);

        $flow = $this->buildFlow();
        $finance = $this->makeV1Finance()[0];
        $quotation = $flow['quotation'];
        $quotation->update(['payment_status' => PaymentStatus::PendingVerification->value]);

        $quotation->paymentNotices()->create([
            'status' => PaymentNoticeStatus::Pending->value,
            'payer_name' => 'Nguyễn Minh Khoa',
            'payer_email' => $quotation->party_email,
            'declared_amount' => $quotation->grand_total,
            'submitted_at' => now(),
        ]);

        $result = app(QuotationPaymentService::class)->updateStatus(
            $quotation->fresh(),
            PaymentStatus::Paid,
            $finance,
            'Đã đối soát thủ công',
        );

        $this->assertSame(PaymentStatus::Paid, $result->payment_status);
    }

    public function test_finance_cannot_mark_paid_without_evidence_when_policy_requires_it(): void
    {
        CompanySetting::firstOrCreateDefault()->update(['payment_evidence_required' => true]);

        $flow = $this->buildFlow();
        $finance = $this->makeV1Finance()[0];
        $quotation = $flow['quotation'];
        $quotation->update(['payment_status' => PaymentStatus::PendingVerification->value]);

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
