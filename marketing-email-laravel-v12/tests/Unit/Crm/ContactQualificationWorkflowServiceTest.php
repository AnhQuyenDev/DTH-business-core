<?php

namespace Tests\Unit\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\QualificationResult;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use App\Services\Crm\ContactQualificationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactQualificationWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ContactQualificationWorkflowService
    {
        return app(ContactQualificationWorkflowService::class);
    }

    private function validQualificationData(array $overrides = []): array
    {
        return array_replace([
            'service_interest' => 'VPS doanh nghiệp',
            'estimated_value' => 30_000_000,
            'budget_status' => 'confirmed_fit',
            'budget_amount' => 30_000_000,
            'purchase_timeline' => 'within_30_days',
            'decision_role' => 'decision_maker',
            'qualification_note' => 'Khách xác nhận nhu cầu, ngân sách và thời gian triển khai.',
            'priority' => 'high',
            'score' => 80,
        ], $overrides);
    }

    public function test_new_cannot_jump_directly_to_contacting(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::New->value,
        ]);

        $this->expectException(ValidationException::class);

        $this->service()->transition(
            $qualification,
            ContactQualificationStatus::Contacting,
        );
    }

    public function test_contacting_can_become_qualified_without_customer_creation(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Contacting->value,
        ]);

        $result = $this->service()->transition(
            qualification: $qualification,
            to: ContactQualificationStatus::Qualified,
            data: $this->validQualificationData(),
        );

        $this->assertSame(ContactQualificationStatus::Qualified, $result->status);
        $this->assertSame(QualificationResult::ConfirmedNeed, $result->qualification_result);
        $this->assertSame('confirmed_fit', $result->budget_status);
        $this->assertSame('within_30_days', $result->purchase_timeline);
        $this->assertSame('decision_maker', $result->decision_role);
        $this->assertNotNull($result->qualified_at);
        $this->assertDatabaseCount('customers', 0);
    }

    public static function requiredQualificationFieldProvider(): array
    {
        return [
            'service interest' => ['service_interest', null],
            'budget status' => ['budget_status', null],
            'purchase timeline' => ['purchase_timeline', null],
            'decision role' => ['decision_role', null],
            'qualification note' => ['qualification_note', ''],
        ];
    }

    #[DataProvider('requiredQualificationFieldProvider')]
    public function test_qualified_requires_complete_business_assessment(string $field, mixed $invalid): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Contacting->value,
            'service_interest' => null,
        ]);

        $data = $this->validQualificationData([$field => $invalid]);

        try {
            $this->service()->transition(
                qualification: $qualification,
                to: ContactQualificationStatus::Qualified,
                data: $data,
            );
            $this->fail("Expected validation error for {$field}.");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
    }

    public function test_budget_amount_must_be_non_negative_when_provided(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Contacting->value,
        ]);

        try {
            $this->service()->transition(
                qualification: $qualification,
                to: ContactQualificationStatus::Qualified,
                data: $this->validQualificationData(['budget_amount' => -1]),
            );
            $this->fail('Negative budget must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('budget_amount', $e->errors());
        }
    }

    public function test_follow_up_requires_next_follow_up_at(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Contacting->value,
        ]);

        try {
            $this->service()->transition(
                $qualification,
                ContactQualificationStatus::FollowUp,
                [],
            );
            $this->fail('Follow-up without a date must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('next_follow_up_at', $e->errors());
        }
    }

    public function test_unqualified_requires_reason_and_valid_result(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Contacting->value,
        ]);

        try {
            $this->service()->transition(
                $qualification,
                ContactQualificationStatus::Unqualified,
                ['qualification_result' => QualificationResult::NoNeed->value],
            );
            $this->fail('Unqualified without reason must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('unqualified_reason', $e->errors());
        }
    }

    public function test_duplicate_and_spam_require_reason(): void
    {
        foreach ([ContactQualificationStatus::Duplicate, ContactQualificationStatus::Spam] as $target) {
            $qualification = ContactQualification::factory()->create([
                'status' => ContactQualificationStatus::Contacting->value,
            ]);

            try {
                $this->service()->transition($qualification, $target, []);
                $this->fail("{$target->value} without reason must be rejected.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('reason', $e->errors());
            }
        }
    }

    public function test_qualified_to_converted_without_payment_service_is_rejected(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Qualified->value,
            'qualification_result' => QualificationResult::ConfirmedNeed->value,
        ]);

        $this->expectException(ValidationException::class);

        $this->service()->transition(
            qualification: $qualification,
            to: ContactQualificationStatus::Converted,
            data: ['converted_customer_id' => 1],
        );
    }

    public function test_qualified_to_converted_requires_converted_customer_id(): void
    {
        $qualification = ContactQualification::factory()->create([
            'status' => ContactQualificationStatus::Qualified->value,
            'qualification_result' => QualificationResult::ConfirmedNeed->value,
        ]);

        try {
            $this->service()->transition(
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

        $result = $this->service()->transition(
            qualification: $qualification,
            to: ContactQualificationStatus::Converted,
            data: ['converted_customer_id' => $customer->id],
            actorUserId: null,
            fromPaymentService: true,
        );

        $this->assertSame(ContactQualificationStatus::Converted, $result->status);
        $this->assertSame(QualificationResult::Purchased, $result->qualification_result);
        $this->assertSame($customer->id, $result->converted_customer_id);
        $this->assertNotNull($result->converted_at);
    }
}
