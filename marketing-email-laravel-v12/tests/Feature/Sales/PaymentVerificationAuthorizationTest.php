<?php

namespace Tests\Feature\Sales;

use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Sales\Concerns\PaymentConversionSetup;
use Tests\TestCase;

class PaymentVerificationAuthorizationTest extends TestCase
{
    use PaymentConversionSetup;
    use RefreshDatabase;

    public function test_admin_can_verify_payments(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('sales.verify-payments'));
    }

    public function test_customer_service_manager_can_verify_payments(): void
    {
        $manager = $this->makeUser('customer_service_manager');
        $this->actingAs($manager);

        $this->assertTrue(Gate::allows('sales.verify-payments'));
    }

    public function test_customer_service_staff_cannot_verify_payments(): void
    {
        $staff = $this->makeUser('customer_service_staff');
        $this->actingAs($staff);

        $this->assertFalse(Gate::allows('sales.verify-payments'));
    }

    public function test_marketing_staff_cannot_verify_payments(): void
    {
        $marketing = $this->makeUser('marketing_staff');
        $this->actingAs($marketing);

        $this->assertFalse(Gate::allows('sales.verify-payments'));
    }

    public function test_confirm_payment_visibility_rules(): void
    {
        $flow = $this->buildFlow();
        $quotation = $flow['quotation'];

        $admin = $flow['admin'];
        $this->actingAs($admin);

        $canVerify = $admin->can('sales.verify-payments');
        $isAccepted = $quotation->status === QuotationStatus::Accepted;
        $paymentStatus = $quotation->payment_status?->value
            ?? (string) $quotation->payment_status;
        $notPaid = $paymentStatus !== PaymentStatus::Paid->value;

        // Accepted + unpaid + gate => confirm action is visible.
        $this->assertTrue($canVerify && $isAccepted && $notPaid);

        // A paid quotation must not expose the confirm action again.
        $quotation->update([
            'payment_status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
        ]);

        $fresh = $quotation->fresh();
        $freshPaymentStatus = $fresh->payment_status?->value
            ?? (string) $fresh->payment_status;

        $this->assertSame(PaymentStatus::Paid->value, $freshPaymentStatus);
        $this->assertFalse(
            $canVerify
            && $fresh->status === QuotationStatus::Accepted
            && $freshPaymentStatus !== PaymentStatus::Paid->value
        );
    }
}
