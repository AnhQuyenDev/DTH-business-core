<?php

namespace Database\Factories\Marketing;

use App\Models\Crm\PersonalContactProfile;
use App\Models\Marketing\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'contact_type' => 'personal',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Contact $contact) {
            PersonalContactProfile::factory()->create([
                'contact_id' => $contact->id,
            ]);
        });
    }

    public function unsubscribed(): static
    {
        return $this;
    }

    public function inactive(): static
    {
        return $this;
    }
}
