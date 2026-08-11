<?php

namespace Tests\Feature\V1;

use App\Enums\Sales\QuotationStatus;
use App\Models\CompanySetting;
use App\Models\Sales\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicQuotationLeanFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_click_confirmation_accepts_without_otp_and_does_not_fake_otp_verification(): void
    {
        Mail::fake();
        config()->set('sales.quotation.accepted_notification_email', '');
        CompanySetting::firstOrCreateDefault()->update(['quotation_confirmation_mode' => 'click']);

        $quotation = Quotation::factory()->sent()->create([
            'customer_snapshot' => [
                'display_name' => 'Nguyễn Minh An',
                'contact_name' => 'Nguyễn Minh An',
                'email' => 'buyer@example.test',
                'customer_type' => 'personal',
            ],
            'metadata' => [
                'authorized_signer' => [
                    'name' => 'Nguyễn Minh An',
                    'email' => 'buyer@example.test',
                ],
            ],
        ]);

        $response = $this->post(route('sales.quotation.public.accept', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Minh An',
            'signer_email' => 'buyer@example.test',
        ]);

        $response->assertRedirect();
        $this->assertSame(QuotationStatus::Accepted, $quotation->fresh()->status);

        $confirmation = $quotation->confirmations()->latest('id')->firstOrFail();
        $this->assertNull($confirmation->otp_verified_at);
        $this->assertSame('public_link', data_get($confirmation->confirmation_data, 'verification_method'));
        $this->assertNull(data_get($confirmation->confirmation_data, 'otp_verified_email'));
    }

    public function test_otp_endpoints_are_disabled_when_click_confirmation_policy_is_active(): void
    {
        CompanySetting::firstOrCreateDefault()->update(['quotation_confirmation_mode' => 'click']);
        $quotation = Quotation::factory()->sent()->create([
            'customer_snapshot' => ['display_name' => 'Buyer', 'email' => 'buyer@example.test'],
        ]);

        $this->post(route('sales.quotation.public.send-otp', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), ['email' => 'buyer@example.test'])->assertNotFound();
    }
}
