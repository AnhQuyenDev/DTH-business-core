<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\PaymentConversionSetup;
use Tests\TestCase;

class AcceptedQuotationDoesNotConvertCustomerTest extends TestCase
{
    use PaymentConversionSetup;
    use RefreshDatabase;

    public function test_accepted_quotation_does_not_create_customer_or_win_opportunity(): void
    {
        $flow = $this->buildFlow();

        $this->assertSame(
            QuotationStatus::Accepted,
            $flow['quotation']->fresh()->status
        );
        $this->assertSame(
            PaymentStatus::Unpaid,
            $flow['quotation']->fresh()->payment_status
        );

        $this->assertNull($flow['quotation']->fresh()->customer_id);
        $this->assertDatabaseCount('customers', 0);

        $opportunity = $flow['opportunity']->fresh();
        $this->assertNotSame(OpportunityStage::Won, $opportunity->stage);
        $this->assertNull($opportunity->won_at);

        $qualification = $flow['qualification']->fresh();
        $this->assertSame(
            ContactQualificationStatus::Qualified,
            $qualification->status
        );
        $this->assertNull($qualification->converted_customer_id);
    }
}
