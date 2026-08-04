<?php

namespace Database\Factories\Sales;

use App\Models\Sales\QuotationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationItemFactory extends Factory
{
    protected $model = QuotationItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->numberBetween(100000, 2000000);
        $lineSubtotal = $quantity * $unitPrice;
        $vatRate = 10;
        $vatAmount = $lineSubtotal * $vatRate / 100;

        return [
            'quotation_id' => \App\Models\Sales\Quotation::factory(),
            'service_name_snapshot' => fake()->words(3, true),
            'package_name_snapshot' => fake()->words(2, true),
            'unit' => 'tháng',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'line_subtotal' => $lineSubtotal,
            'line_total' => $lineSubtotal + $vatAmount,
            'sort_order' => 10,
        ];
    }
}
