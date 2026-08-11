<?php

namespace Tests\Feature\V1;

use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\CompanySetting;
use App\Models\Sales\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicQuotationCustomerJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config()->set('sales.quotation.accepted_notification_email', '');
        CompanySetting::firstOrCreateDefault()->update([
            'quotation_confirmation_mode' => 'click',
            'payment_evidence_required' => false,
        ]);
    }

    private function sentQuotation(): Quotation
    {
        return Quotation::factory()->sent()->create([
            'customer_id' => null,
            'customer_snapshot' => [
                'display_name' => 'Nguyễn Minh An',
                'contact_name' => 'Nguyễn Minh An',
                'email' => 'buyer@example.test',
                'phone' => '0909123456',
                'customer_type' => 'personal',
            ],
            'metadata' => [
                'authorized_signer' => [
                    'name' => 'Nguyễn Minh An',
                    'email' => 'buyer@example.test',
                ],
            ],
            'grand_total' => 11_000_000,
        ]);
    }

    public function test_public_show_tracks_first_view_without_creating_customer(): void
    {
        $quotation = $this->sentQuotation();

        $this->get(route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]))->assertOk();

        $fresh = $quotation->fresh();
        $this->assertSame(QuotationStatus::Viewed, $fresh->status);
        $this->assertSame(1, $fresh->view_count);
        $this->assertNotNull($fresh->first_viewed_at);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_invalid_public_token_is_hidden_as_not_found(): void
    {
        $quotation = $this->sentQuotation();

        $this->get(route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => 'invalid-token',
        ]))->assertNotFound();
    }

    public function test_click_accept_requires_authorized_signer_email(): void
    {
        $quotation = $this->sentQuotation();

        $this->from(route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]))->post(route('sales.quotation.public.accept', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Người lạ',
            'signer_email' => 'other@example.test',
        ])->assertSessionHasErrors('email');

        $this->assertSame(QuotationStatus::Sent, $quotation->fresh()->status);
        $this->assertDatabaseCount('quotation_confirmations', 0);
    }

    public function test_click_accept_creates_confirmation_but_not_customer_or_paid_state(): void
    {
        $quotation = $this->sentQuotation();

        $this->post(route('sales.quotation.public.accept', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Minh An',
            'signer_email' => 'buyer@example.test',
        ])->assertRedirect();

        $fresh = $quotation->fresh();
        $this->assertSame(QuotationStatus::Accepted, $fresh->status);
        $this->assertSame(PaymentStatus::Unpaid, $fresh->payment_status);
        $this->assertNull($fresh->customer_id);
        $this->assertDatabaseCount('customers', 0);

        $confirmation = $fresh->confirmations()->latest('id')->firstOrFail();
        $this->assertNull($confirmation->otp_verified_at);
        $this->assertSame('public_link', data_get($confirmation->confirmation_data, 'verification_method'));
    }

    public function test_click_reject_requires_reason_and_terminates_customer_response(): void
    {
        $quotation = $this->sentQuotation();

        $this->post(route('sales.quotation.public.reject', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Minh An',
            'signer_email' => 'buyer@example.test',
        ])->assertSessionHasErrors('reason');

        $this->post(route('sales.quotation.public.reject', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Minh An',
            'signer_email' => 'buyer@example.test',
            'reason' => 'Chưa phù hợp ngân sách.',
        ])->assertRedirect();

        $this->assertSame(QuotationStatus::Rejected, $quotation->fresh()->status);
    }

    public function test_click_revision_request_requires_reason_and_sets_revision_requested(): void
    {
        $quotation = $this->sentQuotation();

        $this->post(route('sales.quotation.public.request-revision', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Minh An',
            'signer_email' => 'buyer@example.test',
            'reason' => 'Điều chỉnh thời hạn thanh toán.',
        ])->assertRedirect();

        $this->assertSame(QuotationStatus::RevisionRequested, $quotation->fresh()->status);
        $this->assertNotNull($quotation->fresh()->revision_requested_at);
    }

    public function test_customer_can_submit_exact_payment_notice_without_evidence_under_v1_default(): void
    {
        $quotation = $this->sentQuotation();
        $quotation->update([
            'status' => QuotationStatus::Accepted->value,
            'accepted_at' => now(),
        ]);

        $this->post(route('sales.quotation.public.notify-payment', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'payer_name' => 'Nguyễn Minh An',
            'payer_email' => 'buyer@example.test',
            'declared_amount' => 11_000_000,
            'transfer_reference' => 'VCB-001',
        ])->assertRedirect();

        $fresh = $quotation->fresh();
        $this->assertSame(PaymentStatus::PendingVerification, $fresh->payment_status);
        $notice = $fresh->paymentNotices()->firstOrFail();
        $this->assertSame(PaymentNoticeStatus::Pending, $notice->status);
        $this->assertSame('11000000.00', $notice->declared_amount);
        $this->assertDatabaseCount('quotation_payment_notice_files', 0);
    }

    public function test_payment_notice_rejects_wrong_amount_wrong_email_and_duplicate_pending_notice(): void
    {
        $quotation = $this->sentQuotation();
        $quotation->update([
            'status' => QuotationStatus::Accepted->value,
            'accepted_at' => now(),
        ]);
        $route = route('sales.quotation.public.notify-payment', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]);

        $this->post($route, [
            'payer_name' => 'Nguyễn Minh An',
            'payer_email' => 'other@example.test',
            'declared_amount' => 11_000_000,
        ])->assertSessionHasErrors('email');

        $this->post($route, [
            'payer_name' => 'Nguyễn Minh An',
            'payer_email' => 'buyer@example.test',
            'declared_amount' => 10_000_000,
        ])->assertSessionHasErrors('declared_amount');

        $this->post($route, [
            'payer_name' => 'Nguyễn Minh An',
            'payer_email' => 'buyer@example.test',
            'declared_amount' => 11_000_000,
        ])->assertRedirect();

        $this->post($route, [
            'payer_name' => 'Nguyễn Minh An',
            'payer_email' => 'buyer@example.test',
            'declared_amount' => 11_000_000,
        ])->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('quotation_payment_notices', 1);
    }

    public function test_fresh_csrf_endpoint_is_available_for_long_lived_public_tab(): void
    {
        $quotation = $this->sentQuotation();

        $this->getJson(route('sales.quotation.public.csrf-token', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]))->assertOk()->assertJsonStructure(['csrf_token']);
    }
}
