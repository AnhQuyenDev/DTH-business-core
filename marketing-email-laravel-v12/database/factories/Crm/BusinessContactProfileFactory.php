<?php

namespace Database\Factories\Crm;

use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Marketing\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessContactProfileFactory extends Factory
{
    protected $model = BusinessContactProfile::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'company_id' => null,
            'company_name' => fake()->company(),
            'contact_position' => fake()->jobTitle(),
            'business_email' => fake()->safeEmail(),
            'business_phone' => fake()->phoneNumber(),
        ];
    }

    public function forCompany(?Company $company = null): static
    {
        return $this->state(function () use ($company): array {
            $company ??= Company::query()->create([
                'company_code' => 'COM-'.now()->format('Y').str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
                'legal_name' => fake()->company(),
            ]);

            return [
                'company_id' => $company->id,
                'company_name' => $company->legal_name,
            ];
        });
    }
}
