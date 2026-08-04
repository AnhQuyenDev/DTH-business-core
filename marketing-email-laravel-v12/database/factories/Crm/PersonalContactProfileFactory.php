<?php

namespace Database\Factories\Crm;

use App\Models\Crm\PersonalContactProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonalContactProfileFactory extends Factory
{
    protected $model = PersonalContactProfile::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}
