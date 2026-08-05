<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\CustomField;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomFieldFactory extends Factory
{
    protected $model = CustomField::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'type' => $this->faker->randomElement(['text', 'number', 'date', 'boolean']),
            'options' => null,
            'is_required' => false,
            'is_filterable' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
