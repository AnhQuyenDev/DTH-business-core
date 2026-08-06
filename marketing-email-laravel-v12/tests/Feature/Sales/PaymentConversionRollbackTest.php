<?php

namespace Tests\Feature\Sales;

use App\Enums\Sales\PaymentStatus;
use App\Services\Marketing\AuditLogService;
use App\Services\Sales\QuotationPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\Feature\Sales\Concerns\PaymentConversionSetup;
use Tests\TestCase;

class PaymentConversionRollbackTest extends TestCase
{
    use PaymentConversionSetup;
    use RefreshDatabase;

    public function test_exception_in_conversion_rolls_back_everything(): void
    {
        Mail::fake();

        $flow = $this->buildFlow();

        $this->mock(AuditLogService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('log')
                ->andReturnUsing(function (...$args): void {
                    if (($args[0] ?? null) === 'opportunity.converted_to_customer') {
                        throw new RuntimeException('conversion failed');
                    }
                });
        });

        try {
            app(QuotationPaymentService::class)->updateStatus(
                quotation: $flow['quotation'],
                newStatus: PaymentStatus::Paid,
                user: $flow['admin'],
                note: 'Xác minh.',
            );
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('conversion failed', $e->getMessage());
        }

        $quotation = $flow['quotation']->fresh();
        $this->assertNotSame(PaymentStatus::Paid, $quotation->payment_status);
        $this->assertNull($quotation->customer_id);
        $this->assertNull($quotation->paid_at);

        $this->assertDatabaseCount('customers', 0);

        $opportunity = $flow['opportunity']->fresh();
        $this->assertNotSame('won', $opportunity->stage);

        $qualification = $flow['qualification']->fresh();
        $this->assertNotSame('converted', $qualification->status);

        $this->assertNotSame('customer', $flow['company']->fresh()->lifecycle_stage);
    }
}
