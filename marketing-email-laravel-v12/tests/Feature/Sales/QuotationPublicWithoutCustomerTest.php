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
use App\Models\Sales\PriceBookAccessRule;
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

    private User $staffUser;

    private PriceBook $priceBook;

    private PriceBookItem $item;

    private Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();

        $salesDepartment = Department::query()->firstOrCreate(
            ['code' => 'sales'],
            [
                'name' => 'Kinh doanh',
                'function_key' => 'sales',
                'sort_order' => 4,
                'is_active' => true,
            ]
        );

        $this->staffUser = User::query()->create([
            'name' => 'Sales Staff',
            'email' => 'staff-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => 'sales_staff',
        ]);

        $staff = Staff::query()->create([
            'user_id' => $this->staffUser->id,
            'employee_code' => 'NV'.fake()->unique()->numberBetween(1000, 999999),
            'full_name' => 'Sales Staff',
            'department_id' => $salesDepartment->id,
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
            'unit' => 'gói',
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

        PriceBookAccessRule::query()->create([
            'price_book_id' => $this->priceBook->id,
            'access_type' => 'department',
            'department' => 'sales',
            'can_view' => true,
            'can_create_quotation' => true,
        ]);

        $this->opportunity = Opportunity::factory()
            ->qualified()
            ->assignedTo($staff)
            ->create([
                'service_interest' => 'PK-WEB-BASIC',
            ]);
    }

    private function makeSentQuotation(): Quotation
    {
        $quotation = app(QuotationCreationService::class)->createForOpportunity(
            $this->opportunity,
            $this->staffUser,
            $this->priceBook,
            [[
                'price_book_item_id' => $this->item->id,
                'quantity' => 1,
            ]],
        );

        $quotation->update([
            'status' => QuotationStatus::Sent->value,
            'sent_at' => now(),
            'metadata' => array_merge($quotation->metadata ?? [], [
                'authorized_signer' => [
                    'contact_id' => $quotation->contact_id,
                    'name' => $quotation->party_contact_name,
                    'email' => $quotation->party_email,
                ],
            ]),
        ]);

        return $quotation->fresh();
    }

    private function verifyOtpFor(Quotation $quotation): string
    {
        $access = app(QuotationPublicAccessService::class);
        $email = $quotation->party_email;
        $key = $access->otpKey($quotation, $email);
        $access->storeOtp($key, '123456');
        $this->assertTrue($access->verifyOtp($key, '123456'));

        return $email;
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

    public function test_public_show_page_returns_200_and_tracks_view(): void
    {
        $quotation = $this->makeSentQuotation();

        $response = $this->get(route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]));

        $response->assertOk();
        $this->assertSame(QuotationStatus::Viewed, $quotation->fresh()->status);
        $this->assertNotNull($quotation->fresh()->first_viewed_at);
    }

    public function test_first_view_moves_opportunity_to_proposal(): void
    {
        $quotation = $this->makeSentQuotation();

        $access = app(QuotationPublicAccessService::class);
        $found = $access->findQuotation($quotation->quotation_code, $quotation->public_token);
        $access->trackView($found);

        $this->assertSame(
            OpportunityStage::Proposal,
            $found->fresh()->opportunity->fresh()->stage
        );

        $this->assertDatabaseHas('opportunity_interactions', [
            'opportunity_id' => $this->opportunity->id,
            'interaction_type' => 'quotation_viewed',
        ]);
    }

    public function test_subsequent_views_do_not_duplicate_first_view_interaction(): void
    {
        $quotation = $this->makeSentQuotation();
        $access = app(QuotationPublicAccessService::class);

        $access->trackView($access->findQuotation($quotation->quotation_code, $quotation->public_token));
        $access->trackView($access->findQuotation($quotation->quotation_code, $quotation->public_token));

        $this->assertGreaterThanOrEqual(2, $quotation->fresh()->view_count);
        $this->assertSame(1, OpportunityInteraction::where('opportunity_id', $this->opportunity->id)
            ->where('interaction_type', 'quotation_viewed')
            ->count());
    }

    public function test_public_accept_requires_verified_authorized_email_and_moves_to_negotiation(): void
    {
        $quotation = $this->makeSentQuotation();
        $email = $this->verifyOtpFor($quotation);

        $response = $this->post(route('sales.quotation.public.accept', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Văn A',
            'signer_email' => $email,
            'otp_email' => $email,
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
