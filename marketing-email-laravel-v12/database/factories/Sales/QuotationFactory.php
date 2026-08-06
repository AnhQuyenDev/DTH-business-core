<?php

namespace Database\Factories\Sales;

use App\Enums\Sales\EmailStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Staff;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        return [
            'quotation_code' => 'QT'.now()->format('Y').str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'assigned_staff_id' => Staff::factory(),
            'title' => fake()->sentence(3),
            'version' => 1,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'currency' => 'VND',
            'subtotal' => 1000000,
            'discount_total' => 0,
            'tax_total' => 100000,
            'grand_total' => 1100000,
            'status' => QuotationStatus::Draft,
            'payment_status' => PaymentStatus::Unpaid,
            'email_status' => EmailStatus::Unsent,
            'customer_snapshot' => ['name' => fake()->name()],
            'payment_snapshot' => [],
            'terms_snapshot' => ['scope' => fake()->sentence(), 'terms' => fake()->sentence()],
            'public_token' => fake()->sha256(),
            'view_count' => 0,
            'created_by' => 1,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $a) => ['status' => QuotationStatus::Draft]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $a) => [
            'status' => QuotationStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $a) => [
            'status' => QuotationStatus::Accepted,
            'accepted_at' => now(),
            'payment_status' => PaymentStatus::Unpaid,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $a) => [
            'status' => QuotationStatus::Expired,
            'expired_at' => now(),
            'email_status' => EmailStatus::Sent,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $a) => [
            'status' => QuotationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function forOpportunity(
        ?Opportunity $opportunity = null
    ): static {
        return $this->state(function () use (
            $opportunity
        ): array {
            $opportunity ??= Opportunity::factory()->create();

            $opportunity->loadMissing([
                'company',
                'primaryContact.personalProfile',
                'primaryContact.businessProfile',
            ]);

            return [
                'opportunity_id' => $opportunity->id,
                'company_id' => $opportunity->company_id,
                'contact_id' => $opportunity->primary_contact_id,
                'customer_id' => null,
                'assigned_staff_id' => $opportunity->assigned_staff_id,
                'customer_snapshot' => [
                    'id' => null,
                    'display_name' => $opportunity->company?->legal_name
                        ?? $opportunity->primaryContact?->full_name,
                    'customer_type' => $opportunity->company_id
                            ? 'business'
                            : 'personal',
                ],
            ];
        });
    }
}
