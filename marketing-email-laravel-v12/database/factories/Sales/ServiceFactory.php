<?php

namespace Database\Factories\Sales;

use App\Enums\Sales\ServiceStatus;
use App\Models\Sales\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $code = 'SV' . strtoupper(fake()->bothify('??###'));
        return [
            'service_code' => $code,
            'name' => fake()->words(3, true),
            'slug' => str($code)->lower(),
            'description' => fake()->sentence(),
            'status' => ServiceStatus::Active,
            'sort_order' => fake()->numberBetween(1, 100),
            'created_by' => 1,
        ];
    }
}
