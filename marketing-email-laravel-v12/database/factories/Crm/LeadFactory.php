<?php

namespace Database\Factories\Crm;

use App\Enums\Crm\LeadIntakeStatus;
use App\Models\Crm\Company;
use App\Models\Crm\Lead;
use App\Models\Marketing\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'lead_code' => 'LEAD-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'submission_id' => null,
            'contact_id' => Contact::factory(),
            'company_id' => null,
            'source' => 'landing_page',
            'source_detail' => null,
            'title' => 'Yêu cầu tư vấn từ Landing Page',
            'service_interest' => null,
            'estimated_value' => null,
            'intake_status' => LeadIntakeStatus::New->value,
        ];
    }

    public function withCompany(?int $companyId = null): static
    {
        return $this->state(fn (array $a) => [
            'company_id' => $companyId ?? Company::factory(),
        ]);
    }
}
