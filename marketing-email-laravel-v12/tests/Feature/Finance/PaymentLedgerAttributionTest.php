<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Payment;
use App\Models\Sales\QuotationItem;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Services\Finance\PaymentLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\PaymentConversionSetup;
use Tests\TestCase;

class PaymentLedgerAttributionTest extends TestCase
{
    use PaymentConversionSetup;
    use RefreshDatabase;

    public function test_verified_payment_snapshots_product_and_lead_origin_attribution(): void
    {
        $flow = $this->buildFlow();
        $finance = $this->makeV1Finance()[0];

        $flow['lead']->update([
            'source' => 'landing_page',
            'metadata' => [
                'attribution' => [
                    'utm_source' => 'facebook',
                    'utm_medium' => 'paid_social',
                    'utm_campaign' => 'hosting_q3_2026',
                    'utm_content' => 'video_a',
                    'utm_term' => 'hosting doanh nghiep',
                    'referrer' => 'https://facebook.com/',
                ],
            ],
        ]);

        $service = Service::factory()->create();
        $package = ServicePackage::factory()->create(['service_id' => $service->id]);

        QuotationItem::factory()->create([
            'quotation_id' => $flow['quotation']->id,
            'service_id' => $service->id,
            'service_package_id' => $package->id,
            'service_code_snapshot' => $service->service_code,
            'service_name_snapshot' => $service->name,
            'package_code_snapshot' => $package->package_code,
            'package_name_snapshot' => $package->name,
            'quantity' => 1,
            'unit_price' => 1_000_000,
            'line_subtotal' => 1_000_000,
            'discount_amount' => 0,
            'vat_amount' => 100_000,
            'line_total' => 1_100_000,
        ]);

        $payment = app(PaymentLedgerService::class)->recordVerifiedPayment(
            $flow['quotation']->fresh(),
            null,
            $finance,
        );

        $this->assertSame(Payment::STATUS_VERIFIED, $payment->status);
        $this->assertSame('facebook', $payment->attribution?->utm_source);
        $this->assertSame('paid_social', $payment->attribution?->utm_medium);
        $this->assertSame('hosting_q3_2026', $payment->attribution?->utm_campaign);
        $this->assertSame('lead_origin', $payment->attribution?->attribution_model);

        $line = $payment->revenueLines->first();
        $this->assertNotNull($line);
        $this->assertSame($service->id, $line->service_id);
        $this->assertSame($package->id, $line->service_package_id);
        $this->assertSame('1000000.00', $line->net_amount);
        $this->assertSame('100000.00', $line->tax_amount);
        $this->assertSame('1100000.00', $line->gross_amount);

        // Idempotency: one quotation creates only one authoritative payment.
        $again = app(PaymentLedgerService::class)->recordVerifiedPayment(
            $flow['quotation']->fresh(),
            null,
            $finance,
        );

        $this->assertSame($payment->id, $again->id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_attributions', 1);
        $this->assertDatabaseCount('payment_revenue_lines', 1);
    }
}
