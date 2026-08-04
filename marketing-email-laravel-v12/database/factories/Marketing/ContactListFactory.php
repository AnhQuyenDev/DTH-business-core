<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\ContactList;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactListFactory extends Factory
{
    protected $model = ContactList::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'type'        => $this->faker->randomElement(['newsletter', 'promotion', 'event']),
            'status'      => 'active',
        ];
    }
}
