<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->word() . '_' . $this->faker->randomNumber(3),
            'color'       => $this->faker->hexColor(),
            'description' => $this->faker->sentence(),
        ];
    }
}
