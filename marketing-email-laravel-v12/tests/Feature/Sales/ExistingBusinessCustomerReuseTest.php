<?php

namespace Tests\Feature\Sales;

use App\Enums\Sales\PaymentStatus;
use App\Models\Crm\Customer;
use App\Services\Sales\QuotationPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Sales\Concerns\PaymentConversionSetup;
use Tests\TestCase;

class ExistingBusinessCustomerReuseTest extends TestCase
{
    use PaymentConversionSetup;
    use RefreshDatabase;

    public function test_existing_company_customer_is_reused_for_subsequent_purchase(): void
    {
        Mail::fake();

        $first = $this->buildFlow();
        $existing = $this->createExistingCompanyCustomer($first['company'], $first['contact']);

        $second = $this->buildFlow(
            withCompany: true,
            accountOwner: null,
            opportunityOwner: $first['staff'],
            company: $first['company'],
        );

        app(QuotationPaymentService::class)->updateStatus(
            quotation: $second['quotation'],
            newStatus: PaymentStatus::Paid,
            user: $second['admin'],
            note: 'Mua thêm lần hai.',
        );

        $this->assertDatabaseCount('customers', 1);

        $customer = $existing->fresh();
        $this->assertSame($customer->id, $second['quotation']->fresh()->customer_id);

        $this->assertSame(
            (float) $second['quotation']->grand_total
            + 5_000_000,
            (float) $customer->total_revenue
        );

        $this->assertNotNull($customer->latest_purchase_at);

        // The pre-existing (manual) customer had no conversion trace; it is
        // attributed to the first purchasing opportunity, and the quotation
        // is linked to the same customer instead of creating a new one.
        $this->assertSame($second['opportunity']->id, $customer->converted_from_opportunity_id);
        $this->assertSame($customer->id, $second['quotation']->fresh()->customer_id);
    }

    private function createExistingCompanyCustomer($company, $contact): Customer
    {
        return Customer::query()->create([
            'customer_code' => 'CUS-'.fake()->unique()->numerify('######'),
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'customer_type' => 'business',
            'display_name' => $company->legal_name,
            'email' => 'existing-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'status' => 'active',
            'lifecycle_stage' => 'purchasing',
            'consent_status' => 'pending',
            'first_purchase_at' => now()->subMonth(),
            'latest_purchase_at' => now()->subMonth(),
            'total_revenue' => 5_000_000,
        ]);
    }
}
