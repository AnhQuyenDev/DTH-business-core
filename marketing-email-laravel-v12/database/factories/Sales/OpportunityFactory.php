<?php

namespace Database\Factories\Sales;

use App\Enums\Sales\OpportunityStage;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        return [
            'opportunity_code' => 'OPP'.now()->format('Y').str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'primary_contact_id' => Contact::factory(),
            'company_id' => null,
            'assigned_staff_id' => null,
            'title' => fake()->sentence(3),
            'service_interest' => fake()->word(),
            'stage' => OpportunityStage::Qualified->value,
            'estimated_value' => null,
            'probability' => 50,
            'expected_close_date' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function qualified(): static
    {
        return $this->state(fn (array $a) => ['stage' => OpportunityStage::Qualified->value]);
    }

    public function proposal(): static
    {
        return $this->state(fn (array $a) => ['stage' => OpportunityStage::Proposal->value]);
    }

    public function negotiation(): static
    {
        return $this->state(fn (array $a) => ['stage' => OpportunityStage::Negotiation->value]);
    }

    public function won(): static
    {
        return $this->state(fn (array $a) => [
            'stage' => OpportunityStage::Won->value,
            'won_at' => now(),
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $a) => [
            'stage' => OpportunityStage::Lost->value,
            'lost_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $a) => [
            'stage' => OpportunityStage::Cancelled->value,
        ]);
    }

    public function withCompany(?Company $company = null): static
    {
        return $this->state(function () use ($company): array {
            $company ??= Company::query()->create([
                'company_code' => 'COM-'.now()->format('Y').str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
                'legal_name' => fake()->company(),
            ]);

            $contact = Contact::factory()->create();

            BusinessContactProfile::factory()->create([
                'contact_id' => $contact->id,
                'company_id' => $company->id,
                'company_name' => $company->legal_name,
            ]);

            return [
                'company_id' => $company->id,
                'primary_contact_id' => $contact->id,
            ];
        });
    }

    public function assignedTo(?Staff $staff = null): static
    {
        return $this->state(function () use ($staff): array {
            return [
                'assigned_staff_id' => $staff?->id ?? Staff::factory()->create()->id,
            ];
        });
    }
}
