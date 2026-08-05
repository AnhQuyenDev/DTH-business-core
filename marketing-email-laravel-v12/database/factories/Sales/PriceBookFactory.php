<?php

namespace Database\Factories\Sales;

use App\Enums\Sales\AudienceType;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\TaxMode;
use App\Models\Sales\PriceBook;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceBookFactory extends Factory
{
    protected $model = PriceBook::class;

    public function definition(): array
    {
        return [
            'price_book_code' => 'PB'.strtoupper(fake()->bothify('??###')),
            'name' => fake()->words(3, true),
            'audience_type' => AudienceType::Both,
            'currency' => 'VND',
            'tax_mode' => TaxMode::Exclusive,
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => PriceBookStatus::Active,
            'is_default' => false,
            'created_by' => 1,
        ];
    }
}
