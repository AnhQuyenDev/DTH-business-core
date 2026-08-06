<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\CustomerLifecycleStage;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Sales\PaymentStatus;
use App\Models\Crm\Customer;
use App\Models\Marketing\Contact;
use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Sales\Concerns\PaymentConversionSetup;
use Tests\TestCase;

class LegacyPaidQuotationCompatibilityTest extends TestCase
{
    use PaymentConversionSetup;
    use RefreshDatabase;

    public function test_legacy_quotation_with_customer_updates_lifecycle_without_creating_second_customer(): void
    {
        Mail::fake();

        $admin = $this->makeAdminUser();
        $contact = Contact::factory()->create();

        $customer = Customer::query()->create([
            'customer_code' => 'CUS-'.fake()->unique()->numerify('######'),
            'contact_id' => $contact->id,
            'customer_type' => 'personal',
            'display_name' => 'Khách hàng cũ',
            'email' => 'legacy-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'phone' => '0912345678',
            'status' => CustomerStatus::Potential->value,
            'lifecycle_stage' => CustomerLifecycleStage::NewCustomer->value,
            'consent_status' => 'subscribed',
            'total_revenue' => 0,
        ]);

        $quotation = Quotation::query()->create([
            'quotation_code' => 'QT-LEG-'.fake()->unique()->numberBetween(100000, 999999),
            'customer_id' => $customer->id,
            'assigned_staff_id' => $this->makeStaff()->id,
            'title' => 'Báo giá legacy',
            'version' => 1,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'currency' => 'VND',
            'subtotal' => 1_000_000,
            'discount_total' => 0,
            'tax_total' => 100_000,
            'grand_total' => 1_100_000,
            'status' => 'accepted',
            'payment_status' => PaymentStatus::Unpaid->value,
            'email_status' => 'unsent',
            'customer_snapshot' => ['display_name' => 'Khách hàng cũ'],
            'payment_snapshot' => [],
            'terms_snapshot' => [],
            'public_token' => fake()->sha256(),
            'view_count' => 0,
            'created_by' => $admin->id,
        ]);

        $this->assertNull($quotation->opportunity_id);
        $this->assertTrue($quotation->isLegacyCustomerQuotation());

        app(QuotationPaymentService::class)->updateStatus(
            quotation: $quotation,
            newStatus: PaymentStatus::Paid,
            user: $admin,
            note: 'Thanh toán legacy.',
        );

        $this->assertDatabaseCount('customers', 1);

        $fresh = $customer->fresh();
        $this->assertSame(CustomerStatus::Active, $fresh->status);
        $this->assertSame(
            CustomerLifecycleStage::Purchasing->value,
            $fresh->lifecycle_stage
        );
        $this->assertNotNull($fresh->first_purchase_at);
        $this->assertNotNull($fresh->latest_purchase_at);
        $this->assertSame(1_100_000.0, (float) $fresh->total_revenue);
    }
}
