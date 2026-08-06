<?php

namespace Tests\Unit\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\QualificationResult;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
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

    public function test_qualified_to_converted_without_payment_service_is_rejected(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Qualified->value,
            'qualification_result' => QualificationResult::ConfirmedNeed->value,
        ]);

        $this->expectException(ValidationException::class);

        app(ContactQualificationWorkflowService::class)->transition(
            qualification: $qualification,
            to: ContactQualificationStatus::Converted,
            data: [
                'converted_customer_id' => 1,
            ],
        );
    }

    public function test_qualified_to_converted_requires_converted_customer_id(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Qualified->value,
            'qualification_result' => QualificationResult::ConfirmedNeed->value,
        ]);

        try {
            app(ContactQualificationWorkflowService::class)->transition(
                qualification: $qualification,
                to: ContactQualificationStatus::Converted,
                data: [],
                actorUserId: null,
                fromPaymentService: true,
            );
            $this->fail('Missing converted_customer_id should throw ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('converted_customer_id', $e->errors());
        }
    }

    public function test_qualified_to_converted_with_payment_service_passes(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Qualified->value,
            'qualification_result' => QualificationResult::ConfirmedNeed->value,
        ]);

        $customer = Customer::query()->create([
            'customer_code' => 'CUS-'.fake()->unique()->numerify('######'),
            'contact_id' => $qualification->contact_id,
            'customer_type' => 'personal',
            'display_name' => 'Khách hàng chuyển đổi',
            'email' => 'converted-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'status' => 'active',
            'consent_status' => 'pending',
        ]);

        $result = app(ContactQualificationWorkflowService::class)->transition(
            qualification: $qualification,
            to: ContactQualificationStatus::Converted,
            data: [
                'converted_customer_id' => $customer->id,
            ],
            actorUserId: null,
            fromPaymentService: true,
        );

        $this->assertSame(
            ContactQualificationStatus::Converted,
            $result->status
        );
        $this->assertSame(
            QualificationResult::Purchased,
            $result->qualification_result
        );
        $this->assertSame($customer->id, $result->converted_customer_id);
        $this->assertNotNull($result->converted_at);
    }
}
