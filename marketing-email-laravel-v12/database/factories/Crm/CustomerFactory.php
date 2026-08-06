<?php

namespace Database\Factories\Crm;

use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerStatus;
use App\Models\Crm\Customer;
use App\Models\Marketing\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_code' => 'CUS-'.fake()->unique()->numerify('####'),
            'contact_id' => Contact::factory(),
            'customer_type' => fake()->randomElement(['personal', 'business']),
            'display_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'consent_status' => CustomerConsentStatus::Subscribed,
            'status' => CustomerStatus::Active,
        ];
    }
}
