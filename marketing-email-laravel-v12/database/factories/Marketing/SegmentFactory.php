<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\Segment;
use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentFactory extends Factory
{
    protected $model = Segment::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'rules' => ['conditions' => [
                ['field' => 'consent_status_equals', 'operator' => 'equals', 'value' => 'subscribed'],
            ]],
            'status' => 'active',
            'created_by' => null,
        ];
    }
}
