<?php

namespace Tests\Unit\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\QualificationResult;
use App\Models\Crm\ContactQualification;
use App\Services\Crm\ContactQualificationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContactQualificationWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_cannot_jump_directly_to_contacting(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::New->value,
        ]);

        $this->expectException(ValidationException::class);

        app(ContactQualificationWorkflowService::class)->transition(
            $qualification,
            ContactQualificationStatus::Contacting,
        );
    }

    public function test_contacting_can_become_qualified_without_customer_creation(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Contacting->value,
        ]);

        app(ContactQualificationWorkflowService::class)->transition(
            qualification: $qualification,
            to: ContactQualificationStatus::Qualified,
            data: [
                'service_interest' => 'VPS doanh nghiệp',
                'estimated_value' => 30000000,
                'priority' => 'high',
                'score' => 80,
            ],
        );

        $this->assertDatabaseHas('contact_qualifications', [
            'id' => $qualification->id,
            'status' => ContactQualificationStatus::Qualified->value,
            'qualification_result' => QualificationResult::ConfirmedNeed->value,
        ]);

        $this->assertDatabaseCount('customers', 0);
    }
}
