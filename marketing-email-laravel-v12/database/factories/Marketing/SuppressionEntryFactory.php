<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\SuppressionEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuppressionEntryFactory extends Factory
{
    protected $model = SuppressionEntry::class;

    public function definition(): array
    {
        return [
            'email'      => $this->faker->unique()->safeEmail(),
            'reason'     => 'manual',
            'source'     => 'manual',
            'note'       => $this->faker->sentence(),
            'created_by' => null,
        ];
    }
}
