<?php

namespace Database\Factories\Crm;

use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerStatus;
use App\Models\Crm\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_code' => 'CUS-' . fake()->unique()->numerify('####'),
            'customer_type' => fake()->randomElement(['personal', 'business']),
            'display_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'consent_status' => CustomerConsentStatus::Subscribed,
            'status' => CustomerStatus::Active,
            'company_name' => fake()->company(),
        ];
    }
}
