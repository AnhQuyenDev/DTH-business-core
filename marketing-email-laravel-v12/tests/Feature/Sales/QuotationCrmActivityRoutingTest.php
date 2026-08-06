<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\Sales\PackageStatus;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\ServiceStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerInteraction;
use App\Models\Crm\Department;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Models\Sales\OpportunityInteraction;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Quotation;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Models\User;
use App\Services\Sales\QuotationCreationService;
use App\Services\Sales\QuotationInteractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuotationCrmActivityRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PriceBook $priceBook;

    private PriceBookItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);

        $staff = Staff::query()->create([
            'user_id' => $this->admin->id,
            'employee_code' => 'NV'.fake()->unique()->numberBetween(1000, 999999),
            'full_name' => 'Staff',
            'department_id' => Department::query()->firstOrCreate(
                ['code' => 'sales'],
                ['name' => 'Kinh doanh', 'sort_order' => 4, 'is_active' => true]
            )->id,
            'employment_status' => StaffEmploymentStatus::Active,
            'can_receive_customers' => true,
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
            'price_book_code' => 'PBK-CRM-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Bảng giá crm',
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

        $this->opportunity = Opportunity::factory()
            ->qualified()
            ->assignedTo($staff)
            ->create();

        $this->customer = Customer::query()->create([
            'customer_code' => 'CUS-'.strtoupper(Str::random(8)),
            'contact_id' => Contact::factory()->create()->id,
            'customer_type' => 'personal',
            'first_name' => 'Nguyễn',
            'last_name' => 'Văn A',
            'email' => 'a-'.Str::random(4).'@example.test',
            'consent_status' => CustomerConsentStatus::Subscribed,
            'status' => CustomerStatus::Potential,
        ]);
    }

    private Opportunity $opportunity;

    private Customer $customer;

    private function makeOpportunityQuotation(): Quotation
    {
        return app(QuotationCreationService::class)->createForOpportunity(
            $this->opportunity,
            $this->admin,
            $this->priceBook,
            [[
                'price_book_item_id' => $this->item->id,
                'quantity' => 1,
                'unit_price' => 1_000_000,
            ]],
        );
    }

    private function makeLegacyQuotation(): Quotation
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

    public function test_opportunity_quotation_writes_opportunity_interaction_only(): void
    {
        $quotation = $this->makeOpportunityQuotation();

        app(QuotationInteractionService::class)->logSent(
            $quotation,
            'recipient@example.test',
        );

        $this->assertDatabaseHas('opportunity_interactions', [
            'opportunity_id' => $this->opportunity->id,
            'interaction_type' => 'quotation_sent',
        ]);

        $this->assertSame(
            0,
            CustomerInteraction::where('customer_id', $this->customer->id)->count()
        );
    }

    public function test_legacy_quotation_writes_customer_interaction_only(): void
    {
        $quotation = $this->makeLegacyQuotation();

        app(QuotationInteractionService::class)->logSent(
            $quotation,
            'recipient@example.test',
        );

        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $this->customer->id,
            'interaction_type' => 'quotation_sent',
        ]);

        $this->assertSame(
            0,
            OpportunityInteraction::query()->count()
        );
    }

    public function test_sent_and_viewed_events_route_to_opportunity(): void
    {
        $quotation = $this->makeOpportunityQuotation();

        app(QuotationInteractionService::class)->logViewed($quotation);

        $this->assertSame(
            1,
            OpportunityInteraction::where('opportunity_id', $this->opportunity->id)
                ->where('interaction_type', 'quotation_viewed')
                ->count()
        );

        $this->assertSame(
            0,
            CustomerInteraction::where('customer_id', $this->customer->id)->count()
        );
    }

    public function test_accepted_event_routes_by_party(): void
    {
        $oppQuotation = $this->makeOpportunityQuotation();
        app(QuotationInteractionService::class)->logAccepted($oppQuotation, 'Nguyễn Văn A');
        $this->assertDatabaseHas('opportunity_interactions', [
            'opportunity_id' => $this->opportunity->id,
            'interaction_type' => 'quotation_accepted',
        ]);

        $legacyQuotation = $this->makeLegacyQuotation();
        app(QuotationInteractionService::class)->logAccepted($legacyQuotation, 'Nguyễn Văn A');
        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $this->customer->id,
            'interaction_type' => 'quotation_accepted',
        ]);

        $this->assertSame(
            1,
            CustomerInteraction::where('customer_id', $this->customer->id)
                ->where('interaction_type', 'quotation_accepted')
                ->count()
        );
    }
}
