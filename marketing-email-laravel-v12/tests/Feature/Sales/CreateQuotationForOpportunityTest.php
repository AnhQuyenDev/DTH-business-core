<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\Sales\AudienceType;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PackageStatus;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\ServiceStatus;
use App\Models\Crm\Department;
use App\Models\Crm\Staff;
use App\Models\Sales\Opportunity;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookAccessRule;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Models\User;
use App\Services\Sales\QuotationCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class CreateQuotationForOpportunityTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    private function makeAssignedUser(): User
    {
        return $this->makeV1SalesStaff('Quotation Sales Staff')[0];
    }

    private function makeQualifiedOpportunity(Staff $staff): Opportunity
    {
        return Opportunity::factory()
            ->qualified()
            ->withCompany()
            ->assignedTo($staff)
            ->create();
    }

    private function makeAccessiblePriceBook(User $user, string $audience): PriceBook
    {
        $audienceType = AudienceType::from($audience);

        $service = Service::query()->create([
            'service_code' => 'SV-'.strtoupper($audience),
            'name' => 'Dịch vụ '.$audience,
            'slug' => 'dich-vu-'.$audience,
            'status' => ServiceStatus::Active,
        ]);

        $package = ServicePackage::query()->create([
            'service_id' => $service->id,
            'package_code' => 'PK-'.strtoupper($audience),
            'name' => 'Gói '.$audience,
            'audience_type' => $audienceType,
            'unit' => 'tháng',
            'default_quantity' => 1,
            'status' => PackageStatus::Active,
        ]);

        $priceBook = PriceBook::query()->create([
            'price_book_code' => 'PBK-'.strtoupper($audience).fake()->unique()->numberBetween(1, 999999),
            'name' => 'Bảng giá '.$audience,
            'audience_type' => $audienceType,
            'currency' => 'VND',
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => PriceBookStatus::Active,
        ]);

        PriceBookItem::query()->create([
            'price_book_id' => $priceBook->id,
            'service_package_id' => $package->id,
            'unit_price' => 1_000_000,
            'vat_rate' => 10,
            'sort_order' => 1,
        ]);

        PriceBookAccessRule::query()->create([
            'price_book_id' => $priceBook->id,
            'access_type' => 'department',
            'department' => $user->staff->department->code,
            'can_view' => true,
            'can_create_quotation' => true,
        ]);

        return $priceBook->fresh();
    }

    private function validItems(PriceBook $priceBook): array
    {
        $item = PriceBookItem::where('price_book_id', $priceBook->id)->firstOrFail();

        return [[
            'price_book_item_id' => $item->id,
            'quantity' => 1,
            'unit_price' => 1_000_000,
        ]];
    }

    public function test_qualified_opportunity_creates_quotation_without_customer(): void
    {
        config()->set('business_flow.opportunity_quotation_enabled', true);

        $user = $this->makeAssignedUser();
        $opportunity = $this->makeQualifiedOpportunity($user->staff);
        $priceBook = $this->makeAccessiblePriceBook($user, 'business');

        $quotation = app(QuotationCreationService::class)
            ->createForOpportunity(
                opportunity: $opportunity,
                user: $user,
                priceBook: $priceBook,
                items: $this->validItems($priceBook),
            );

        $this->assertNull($quotation->customer_id);
        $this->assertSame($opportunity->id, $quotation->opportunity_id);
        $this->assertSame($opportunity->company_id, $quotation->company_id);
        $this->assertSame($opportunity->primary_contact_id, $quotation->contact_id);
        $this->assertSame($user->staff->id, $quotation->assigned_staff_id);
        $this->assertNotEmpty($quotation->customer_snapshot);
        $this->assertNotEmpty($quotation->company_snapshot);
        $this->assertSame('business', $quotation->party_type);
        $this->assertTrue($quotation->isOpportunityQuotation());
        $this->assertFalse($quotation->isLegacyCustomerQuotation());
    }

    public function test_proposal_and_negotiation_opportunities_can_create_quotation(): void
    {
        $user = $this->makeAssignedUser();
        $priceBook = $this->makeAccessiblePriceBook($user, 'business');

        foreach ([OpportunityStage::Proposal, OpportunityStage::Negotiation] as $stage) {
            $opportunity = Opportunity::factory()
                ->state(['stage' => $stage->value])
                ->withCompany()
                ->assignedTo($user->staff)
                ->create();

            $quotation = app(QuotationCreationService::class)
                ->createForOpportunity(
                    $opportunity,
                    $user,
                    $priceBook,
                    $this->validItems($priceBook),
                );

            $this->assertSame($opportunity->id, $quotation->opportunity_id);
            $this->assertNull($quotation->customer_id);
        }
    }

    public function test_terminal_opportunity_cannot_create_quotation(): void
    {
        $user = $this->makeAssignedUser();
        $priceBook = $this->makeAccessiblePriceBook($user, 'business');

        foreach ([OpportunityStage::Won, OpportunityStage::Lost, OpportunityStage::Cancelled] as $stage) {
            $opportunity = Opportunity::factory()
                ->state(['stage' => $stage->value])
                ->withCompany()
                ->assignedTo($user->staff)
                ->create();

            try {
                app(QuotationCreationService::class)->createForOpportunity(
                    $opportunity,
                    $user,
                    $priceBook,
                    $this->validItems($priceBook),
                );
                $this->fail("Opportunity stage {$stage->value} should not create quotation.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('opportunity_id', $e->errors());
            }
        }
    }

    public function test_staff_not_assigned_to_opportunity_cannot_create_quotation(): void
    {
        $user = $this->makeAssignedUser();
        $priceBook = $this->makeAccessiblePriceBook($user, 'business');

        $owner = $this->makeAssignedUser();
        $opportunity = $this->makeQualifiedOpportunity($owner->staff);

        $this->expectException(ValidationException::class);

        app(QuotationCreationService::class)->createForOpportunity(
            $opportunity,
            $user,
            $priceBook,
            $this->validItems($priceBook),
        );
    }

    public function test_personal_price_book_cannot_be_used_for_business_opportunity(): void
    {
        $user = $this->makeAssignedUser();
        $personalBook = $this->makeAccessiblePriceBook($user, 'personal');
        $opportunity = $this->makeQualifiedOpportunity($user->staff);

        try {
            app(QuotationCreationService::class)->createForOpportunity(
                $opportunity,
                $user,
                $personalBook,
                $this->validItems($personalBook),
            );
            $this->fail('Personal price book must not be usable for a business opportunity.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('price_book_id', $e->errors());
        }
    }
}
