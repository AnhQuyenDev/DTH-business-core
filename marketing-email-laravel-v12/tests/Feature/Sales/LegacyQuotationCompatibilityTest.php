<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Sales\PackageStatus;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\QuotationStatus;
use App\Enums\Sales\ServiceStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerInteraction;
use App\Models\Marketing\Contact;
use App\Models\Sales\OpportunityInteraction;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Quotation;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Models\User;
use App\Services\Sales\QuotationCreationService;
use App\Services\Sales\QuotationPublicAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyQuotationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PriceBook $priceBook;

    private PriceBookItem $item;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);

        $service = Service::query()->create([
            'service_code' => 'SV-WEB',
            'name' => 'Thiết kế website',
            'slug' => 'thiet-ke-website',
            'status' => ServiceStatus::Active,
        ]);

        $package = ServicePackage::query()->create([
            'service_id' => $service->id,
            'package_code' => 'PK-WEB-BASIC',
            'name' => 'Gói website cơ bản',
            'audience_type' => 'both',
            'unit' => 'tháng',
            'default_quantity' => 1,
            'status' => PackageStatus::Active,
        ]);

        $this->priceBook = PriceBook::query()->create([
            'price_book_code' => 'PBK-LEG-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Bảng giá legacy',
            'audience_type' => 'both',
            'currency' => 'VND',
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => PriceBookStatus::Active,
        ]);

        $this->item = PriceBookItem::query()->create([
            'price_book_id' => $this->priceBook->id,
            'service_package_id' => $package->id,
            'unit_price' => 1_000_000,
            'vat_rate' => 10,
            'sort_order' => 1,
        ]);

        $this->customer = Customer::query()->create([
            'customer_code' => 'CUS-'.strtoupper(Str::random(8)),
            'contact_id' => Contact::factory()->create()->id,
            'customer_type' => 'personal',
            'first_name' => 'Nguyễn',
            'last_name' => 'Văn A',
            'email' => 'a-'.Str::random(4).'@example.test',
            'phone' => '0912345678',
            'consent_status' => CustomerConsentStatus::Subscribed,
            'status' => CustomerStatus::Potential,
        ]);
    }

    private function createLegacyQuotation(): Quotation
    {
        return app(QuotationCreationService::class)->create(
            $this->customer,
            $this->admin,
            $this->priceBook,
            [[
                'price_book_item_id' => $this->item->id,
                'quantity' => 1,
                'unit_price' => 1_000_000,
            ]],
        );
    }

    public function test_legacy_create_still_works_and_saves_customer_id(): void
    {
        $quotation = $this->createLegacyQuotation();

        $this->assertSame($this->customer->id, $quotation->customer_id);
        $this->assertNull($quotation->opportunity_id);
        $this->assertTrue($quotation->isLegacyCustomerQuotation());
        $this->assertFalse($quotation->isOpportunityQuotation());
    }

    public function test_party_accessors_read_customer_snapshot(): void
    {
        $quotation = $this->createLegacyQuotation();

        $this->assertSame(
            $this->customer->display_name,
            $quotation->party_display_name
        );
        $this->assertSame(
            $this->customer->email,
            $quotation->party_email
        );
        $this->assertSame(
            $this->customer->phone,
            $quotation->party_phone
        );
        $this->assertSame('personal', $quotation->party_type);
    }

    public function test_legacy_public_workflow_still_records_customer_interaction(): void
    {
        $quotation = $this->createLegacyQuotation();
        $quotation->update([
            'status' => QuotationStatus::Sent->value,
            'sent_at' => now(),
        ]);

        $found = app(QuotationPublicAccessService::class)
            ->findQuotation($quotation->quotation_code, $quotation->public_token);

        $this->assertNotNull($found);

        $response = $this->get(route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]));

        $response->assertOk();

        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $this->customer->id,
            'interaction_type' => 'quotation_viewed',
        ]);

        $this->assertSame(
            0,
            OpportunityInteraction::query()->count()
        );
    }

    public function test_legacy_quotation_accepts_through_public_route(): void
    {
        $quotation = $this->createLegacyQuotation();
        $quotation->update([
            'status' => QuotationStatus::Sent->value,
            'sent_at' => now(),
        ]);

        $response = $this->post(route('sales.quotation.public.accept', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Văn A',
            'signer_email' => 'a@example.test',
        ]);

        $response->assertRedirect();

        $this->assertSame(QuotationStatus::Accepted, $quotation->fresh()->status);

        $interaction = CustomerInteraction::where('customer_id', $this->customer->id)
            ->where('interaction_type', 'quotation_accepted')
            ->first();
        $this->assertNotNull($interaction);
    }
}
