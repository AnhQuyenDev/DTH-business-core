<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\Sales\DiscountType;
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

class QuotationPriceBookIntegrityTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    private User $staffUser;

    private Staff $staff;

    private ServicePackage $package;

    private PriceBook $priceBook;

    private PriceBookItem $item;

    private Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->staffUser, $this->staff] = $this->makeV1SalesStaff('Price Book Sales Staff');

        $service = Service::query()->create([
            'service_code' => 'HOSTING',
            'name' => 'Pro Platinum Hosting',
            'slug' => 'pro-platinum-hosting',
            'status' => ServiceStatus::Active,
        ]);

        $this->package = ServicePackage::query()->create([
            'service_id' => $service->id,
            'package_code' => 'PPH02',
            'name' => 'Pro Platinum Hosting Pro',
            'audience_type' => 'business',
            'unit' => 'gói',
            'default_quantity' => 1,
            'status' => PackageStatus::Active,
        ]);

        $this->priceBook = PriceBook::query()->create([
            'price_book_code' => 'PB-HOSTING-B2B-TEST',
            'name' => 'Bảng giá Hosting doanh nghiệp',
            'audience_type' => 'business',
            'currency' => 'VND',
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => PriceBookStatus::Active,
        ]);

        $this->item = PriceBookItem::query()->create([
            'price_book_id' => $this->priceBook->id,
            'service_package_id' => $this->package->id,
            'unit_price' => 3_500_000,
            'minimum_quantity' => 1,
            'maximum_quantity' => 5,
            'default_discount_type' => DiscountType::Percentage,
            'default_discount_value' => 0,
            'maximum_discount_value' => 10,
            'vat_rate' => 10,
            'sort_order' => 1,
        ]);

        PriceBookAccessRule::query()->create([
            'price_book_id' => $this->priceBook->id,
            'access_type' => 'department',
            'department' => $this->staff->department->code,
            'can_view' => true,
            'can_create_quotation' => true,
        ]);

        $this->opportunity = Opportunity::factory()
            ->proposal()
            ->withCompany()
            ->assignedTo($this->staff)
            ->create([
                'service_interest' => 'PPH02',
            ]);
    }

    public function test_price_book_fields_are_server_authoritative(): void
    {
        $quotation = app(QuotationCreationService::class)->createForOpportunity(
            $this->opportunity,
            $this->staffUser,
            $this->priceBook,
            [[
                'price_book_item_id' => $this->item->id,
                'quantity' => 1,
                'unit' => 'VND',
                'unit_price' => 1,
                'vat_rate' => 0,
                'discount_type' => DiscountType::Fixed->value,
                'discount_value' => 5,
            ]],
        );

        $line = $quotation->items()->firstOrFail();

        $this->assertSame('gói', $line->unit);
        $this->assertSame(3_500_000.0, (float) $line->unit_price);
        $this->assertSame(10.0, (float) $line->vat_rate);
        $this->assertSame(DiscountType::Percentage, $line->discount_type);
        $this->assertSame('PPH02', $line->package_code_snapshot);
    }

    public function test_discount_above_price_book_limit_is_rejected_with_validation_error(): void
    {
        try {
            app(QuotationCreationService::class)->createForOpportunity(
                $this->opportunity,
                $this->staffUser,
                $this->priceBook,
                [[
                    'price_book_item_id' => $this->item->id,
                    'quantity' => 1,
                    'discount_value' => 20,
                ]],
            );

            $this->fail('Discount above 10% must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
            $this->assertStringContainsString('10.00%', $exception->errors()['items'][0]);
        }
    }

    public function test_quantity_outside_price_book_range_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(QuotationCreationService::class)->createForOpportunity(
            $this->opportunity,
            $this->staffUser,
            $this->priceBook,
            [[
                'price_book_item_id' => $this->item->id,
                'quantity' => 6,
            ]],
        );
    }

    public function test_opportunity_quotation_uses_price_book_authority_over_opportunity_interest(): void
    {
        $otherPackage = ServicePackage::query()->create([
            'service_id' => $this->package->service_id,
            'package_code' => 'PPH03',
            'name' => 'Pro Platinum Hosting VIP',
            'audience_type' => 'business',
            'unit' => 'gói',
            'default_quantity' => 1,
            'status' => PackageStatus::Active,
        ]);

        $otherItem = PriceBookItem::query()->create([
            'price_book_id' => $this->priceBook->id,
            'service_package_id' => $otherPackage->id,
            'unit_price' => 5_000_000,
            'vat_rate' => 10,
        ]);

        // service_interest chỉ là attribution/context; bảng giá đã chọn là
        // nguồn thương mại duy nhất, nên gói khác vẫn được báo giá hợp lệ.
        $quotation = app(QuotationCreationService::class)->createForOpportunity(
            $this->opportunity,
            $this->staffUser,
            $this->priceBook,
            [[
                'price_book_item_id' => $otherItem->id,
                'quantity' => 1,
            ]],
        );

        $line = $quotation->items()->firstOrFail();
        $this->assertSame('PPH03', $line->package_code_snapshot);
    }
}
