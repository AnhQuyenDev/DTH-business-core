<?php

namespace Database\Factories\Sales;

use App\Models\Sales\PriceBookItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceBookItemFactory extends Factory
{
    protected $model = PriceBookItem::class;

    public function definition(): array
    {
        return [
            'price_book_id' => \App\Models\Sales\PriceBook::factory(),
            'service_package_id' => \App\Models\Sales\ServicePackage::factory(),
            'unit_price' => fake()->numberBetween(100000, 5000000),
            'vat_rate' => 10,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
