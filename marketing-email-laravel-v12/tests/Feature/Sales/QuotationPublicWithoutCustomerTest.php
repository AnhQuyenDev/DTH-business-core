<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PackageStatus;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\QuotationStatus;
use App\Enums\Sales\ServiceStatus;
use App\Models\Crm\Department;
use App\Models\Crm\Staff;
use App\Models\Sales\Opportunity;
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
use Tests\TestCase;

class QuotationPublicWithoutCustomerTest extends TestCase
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

        $staffUser = User::query()->create([
            'name' => 'Staff',
            'email' => 'staff-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => 'customer_service_staff',
        ]);

        $staff = Staff::query()->create([
            'user_id' => $staffUser->id,
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
            'price_book_code' => 'PBK-PUB-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Bảng giá công khai',
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
    }

    private Opportunity $opportunity;

    private function makeSentQuotation(): Quotation
    {
        $quotation = app(QuotationCreationService::class)->createForOpportunity(
            $this->opportunity,
            $this->admin,
            $this->priceBook,
            [[
                'price_book_item_id' => $this->item->id,
                'quantity' => 1,
                'unit_price' => 1_000_000,
            ]],
        );

        $quotation->update([
            'status' => QuotationStatus::Sent->value,
            'sent_at' => now(),
        ]);

        return $quotation->fresh();
    }

    public function test_public_token_finds_quotation_without_customer(): void
    {
        $quotation = $this->makeSentQuotation();
        $this->assertNull($quotation->customer_id);

        $found = app(QuotationPublicAccessService::class)
            ->findQuotation($quotation->quotation_code, $quotation->public_token);

        $this->assertInstanceOf(Quotation::class, $found);
        $this->assertSame($quotation->id, $found->id);
        $this->assertNull(app(QuotationPublicAccessService::class)
            ->findQuotation($quotation->quotation_code, 'bogus'));
    }

    public function test_public_show_page_returns_200(): void
    {
        $quotation = $this->makeSentQuotation();

        $response = $this->get(route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]));

        $response->assertOk();
    }

    public function test_first_view_sets_timestamps_and_moves_opportunity_to_proposal(): void
    {
        $quotation = $this->makeSentQuotation();

        $access = app(QuotationPublicAccessService::class);
        $found = $access->findQuotation($quotation->quotation_code, $quotation->public_token);

        $access->trackView($found);

        $fresh = $found->fresh();
        $this->assertSame(QuotationStatus::Viewed, $fresh->status);
        $this->assertNotNull($fresh->first_viewed_at);
        $this->assertNotNull($fresh->last_viewed_at);

        $this->assertSame(
            OpportunityStage::Proposal,
            $fresh->opportunity->fresh()->stage
        );

        $this->assertDatabaseHas('opportunity_interactions', [
            'opportunity_id' => $this->opportunity->id,
            'interaction_type' => 'quotation_viewed',
        ]);
    }

    public function test_subsequent_views_increment_count_without_new_interaction(): void
    {
        $quotation = $this->makeSentQuotation();
        $access = app(QuotationPublicAccessService::class);

        $access->trackView($access->findQuotation($quotation->quotation_code, $quotation->public_token));
        $access->trackView($access->findQuotation($quotation->quotation_code, $quotation->public_token));

        $fresh = $quotation->fresh();
        $this->assertGreaterThanOrEqual(1, $fresh->view_count);
        $this->assertSame(1, OpportunityInteraction::where('opportunity_id', $this->opportunity->id)
            ->where('interaction_type', 'quotation_viewed')
            ->count());
    }

    public function test_public_accept_routes_without_customer_and_moves_to_negotiation(): void
    {
        $quotation = $this->makeSentQuotation();

        $response = $this->post(route('sales.quotation.public.accept', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Văn A',
            'signer_email' => 'a@example.test',
        ]);

        $response->assertRedirect();

        $this->assertSame(QuotationStatus::Accepted, $quotation->fresh()->status);
        $this->assertSame(
            OpportunityStage::Negotiation,
            $this->opportunity->fresh()->stage
        );

        $this->assertDatabaseHas('opportunity_interactions', [
            'opportunity_id' => $this->opportunity->id,
            'interaction_type' => 'quotation_accepted',
        ]);
    }
}
