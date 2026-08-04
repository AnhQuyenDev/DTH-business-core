<?php

namespace Tests\Unit\Sales;

use App\Enums\Sales\DiscountType;
use App\Services\Sales\QuotationPricingService;
use PHPUnit\Framework\TestCase;

class QuotationPricingServiceTest extends TestCase
{
    private QuotationPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuotationPricingService;
    }

    public function test_calculates_subtotal(): void
    {
        $result = $this->service->calculateItem([
            'quantity' => 2,
            'unit_price' => 500000,
            'vat_rate' => 10,
        ]);

        $this->assertEquals(1000000, $result['line_subtotal']);
    }

    public function test_fixed_discount(): void
    {
        $result = $this->service->calculateItem([
            'quantity' => 1,
            'unit_price' => 1000000,
            'discount_type' => DiscountType::Fixed->value,
            'discount_value' => 200000,
            'vat_rate' => 10,
        ]);

        $this->assertEquals(1000000, $result['line_subtotal']);
        $this->assertEquals(200000, $result['discount_amount']);
        $this->assertEquals(80000, $result['vat_amount']);
        $this->assertEquals(880000, $result['line_total']);
    }

    public function test_percentage_discount(): void
    {
        $result = $this->service->calculateItem([
            'quantity' => 1,
            'unit_price' => 1000000,
            'discount_type' => DiscountType::Percentage->value,
            'discount_value' => 10,
            'vat_rate' => 10,
        ]);

        $this->assertEquals(1000000, $result['line_subtotal']);
        $this->assertEquals(100000, $result['discount_amount']);
        $this->assertEquals(90000, $result['vat_amount']);
        $this->assertEquals(990000, $result['line_total']);
    }

    public function test_discount_does_not_exceed_line_subtotal(): void
    {
        $result = $this->service->calculateItem([
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_type' => DiscountType::Fixed->value,
            'discount_value' => 999999,
            'vat_rate' => 10,
        ]);

        $this->assertEquals(100000, $result['discount_amount']);
        $this->assertEquals(0, $result['line_total']);
    }

    public function test_percentage_capped_at_100(): void
    {
        $result = $this->service->calculateItem([
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_type' => DiscountType::Percentage->value,
            'discount_value' => 150,
            'vat_rate' => 10,
        ]);

        $this->assertEquals(100000, $result['discount_amount']);
    }

    public function test_no_vat(): void
    {
        $result = $this->service->calculateItem([
            'quantity' => 1,
            'unit_price' => 1000000,
            'vat_rate' => 0,
        ]);

        $this->assertEquals(1000000, $result['line_subtotal']);
        $this->assertEquals(0, $result['vat_amount']);
        $this->assertEquals(1000000, $result['line_total']);
    }

    public function test_multiple_items_totals(): void
    {
        $items = collect([
            ['quantity' => 2, 'unit_price' => 500000, 'vat_rate' => 10],
            ['quantity' => 1, 'unit_price' => 300000, 'vat_rate' => 10],
            ['quantity' => 3, 'unit_price' => 100000, 'vat_rate' => 10],
        ]);

        $totals = $this->service->calculateTotals($items);

        $this->assertEquals(1600000, $totals['subtotal']);
        $this->assertEquals(0, $totals['discount_total']);
        $this->assertEquals(160000, $totals['tax_total']);
        $this->assertEquals(1760000, $totals['grand_total']);
    }

    public function test_vnd_rounding(): void
    {
        $result = $this->service->calculateItem([
            'quantity' => 1,
            'unit_price' => 999999,
            'vat_rate' => 10,
        ]);

        $this->assertEquals(999999, $result['line_subtotal']);
        $this->assertEquals(99999.9, $result['vat_amount']);
        $this->assertEquals(1099998.9, $result['line_total']);
    }

    public function test_discount_limit_validation(): void
    {
        $this->assertTrue($this->service->validateDiscountLimit(50000, 100000));
        $this->assertTrue($this->service->validateDiscountLimit(100000, 100000));
        $this->assertFalse($this->service->validateDiscountLimit(150000, 100000));
    }

    public function test_discount_with_vat(): void
    {
        $result = $this->service->calculateItem([
            'quantity' => 5,
            'unit_price' => 200000,
            'discount_type' => DiscountType::Percentage->value,
            'discount_value' => 15,
            'vat_rate' => 10,
        ]);

        $this->assertEquals(1000000, $result['line_subtotal']);
        $this->assertEquals(150000, $result['discount_amount']);
        $this->assertEquals(85000, $result['vat_amount']);
        $this->assertEquals(935000, $result['line_total']);
    }
}
