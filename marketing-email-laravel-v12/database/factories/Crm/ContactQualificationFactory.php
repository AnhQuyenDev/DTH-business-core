<?php

namespace Database\Factories\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactQualificationFactory extends Factory
{
    protected $model = ContactQualification::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'lead_id' => Lead::factory(),
            'status' => ContactQualificationStatus::New->value,
            'priority' => 'normal',
            'score' => null,
            'qualification_result' => null,
            'service_interest' => null,
            'estimated_value' => null,
            'first_contacted_at' => null,
            'last_contacted_at' => null,
            'next_follow_up_at' => null,
            'qualified_at' => null,
            'qualified_by_staff_id' => null,
            'unqualified_reason' => null,
            'converted_at' => null,
            'converted_customer_id' => null,
        ];
    }

    public function withLead(?int $leadId = null): static
    {
        return $this->state(fn (array $a) => [
            'lead_id' => $leadId ?? Lead::factory(),
        ]);
    }

    public function withStaff(?int $staffId = null): static
    {
        return $this->state(fn (array $a) => [
            'assigned_staff_id' => $staffId ?? Staff::factory(),
        ]);
    }

    public function status(ContactQualificationStatus $status): static
    {
        return $this->state(fn (array $a) => ['status' => $status->value]);
    }
}
