<?php

namespace Tests\Feature\Compatibility;

use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerLifecycleStage;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Sales\PackageStatus;
use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\QuotationStatus;
use App\Enums\Sales\ServiceStatus;
use App\Models\Crm\Customer;
use App\Models\Marketing\Contact;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Quotation;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Services\Sales\QuotationCreationService;
use App\Services\Sales\QuotationPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class LegacyQuotationCompatibilityTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    private function catalog(): array
    {
        $service = Service::query()->create([
            'service_code' => 'SV-LEGACY',
            'name' => 'Dịch vụ legacy',
            'slug' => 'dich-vu-legacy-'.fake()->unique()->numberBetween(1, 999999),
            'status' => ServiceStatus::Active,
        ]);
        $package = ServicePackage::query()->create([
            'service_id' => $service->id,
            'package_code' => 'PK-LEGACY-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Gói legacy',
            'audience_type' => 'both',
            'unit' => 'tháng',
            'default_quantity' => 1,
            'status' => PackageStatus::Active,
        ]);
        $book = PriceBook::query()->create([
            'price_book_code' => 'PBK-LEG-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Bảng giá legacy',
            'audience_type' => 'both',
            'currency' => 'VND',
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => PriceBookStatus::Active,
        ]);
        $item = PriceBookItem::query()->create([
            'price_book_id' => $book->id,
            'service_package_id' => $package->id,
            'unit_price' => 1_000_000,
            'vat_rate' => 10,
            'sort_order' => 1,
        ]);

        return [$book, $item];
    }

    private function customer(): Customer
    {
        return Customer::query()->create([
            'customer_code' => 'CUS-'.strtoupper(Str::random(8)),
            'contact_id' => Contact::factory()->create()->id,
            'customer_type' => 'personal',
            'first_name' => 'Nguyễn',
            'last_name' => 'Văn A',
            'email' => 'legacy-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'phone' => '0912345678',
            'consent_status' => CustomerConsentStatus::Subscribed,
            'status' => CustomerStatus::Potential,
            'lifecycle_stage' => CustomerLifecycleStage::NewCustomer,
            'total_revenue' => 0,
        ]);
    }

    private function createLegacyQuotation(): Quotation
    {
        [$book, $item] = $this->catalog();
        $customer = $this->customer();
        $admin = $this->makeV1SystemAdmin('Legacy Admin');

        return app(QuotationCreationService::class)->create(
            $customer,
            $admin,
            $book,
            [[
                'price_book_item_id' => $item->id,
                'quantity' => 1,
                'unit_price' => 1_000_000,
            ]],
        );
    }

    public function test_existing_customer_quotation_remains_readable_and_publicly_confirmable(): void
    {
        $quotation = $this->createLegacyQuotation();
        $this->assertNotNull($quotation->customer_id);
        $this->assertNull($quotation->opportunity_id);
        $this->assertTrue($quotation->isLegacyCustomerQuotation());
        $this->assertSame($quotation->customer->email, $quotation->party_email);

        $quotation->update([
            'status' => QuotationStatus::Sent->value,
            'sent_at' => now(),
        ]);

        $this->get(route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]))->assertOk();

        $this->post(route('sales.quotation.public.accept', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Văn A',
            'signer_email' => $quotation->party_email,
        ])->assertRedirect();

        $this->assertSame(QuotationStatus::Accepted, $quotation->fresh()->status);
        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $quotation->customer_id,
            'interaction_type' => 'quotation_accepted',
        ]);
    }

    public function test_legacy_paid_flow_reuses_existing_customer_under_current_finance_rules(): void
    {
        Mail::fake();
        $quotation = $this->createLegacyQuotation();
        $customer = $quotation->customer;
        $quotation->update([
            'status' => QuotationStatus::Accepted->value,
            'accepted_at' => now(),
            'payment_status' => PaymentStatus::PendingVerification->value,
        ]);
        $quotation->paymentNotices()->create([
            'status' => PaymentNoticeStatus::Pending->value,
            'payer_name' => $customer->display_name,
            'payer_email' => $quotation->party_email,
            'declared_amount' => $quotation->grand_total,
            'submitted_at' => now(),
        ]);
        [$finance] = $this->makeV1Finance('Legacy Finance');

        app(QuotationPaymentService::class)->updateStatus(
            $quotation->fresh(),
            PaymentStatus::Paid,
            $finance,
            'Đối soát legacy theo V1 hiện tại.',
        );

        $this->assertDatabaseCount('customers', 1);
        $fresh = $customer->fresh();
        $this->assertSame(CustomerStatus::Active, $fresh->status);
        $this->assertSame(
            CustomerLifecycleStage::Purchasing->value,
            $fresh->lifecycle_stage,
        );
        $this->assertNotNull($fresh->first_purchase_at);
        $this->assertSame((float) $quotation->grand_total, (float) $fresh->total_revenue);
    }
}
