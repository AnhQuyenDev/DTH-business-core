<?php

namespace Tests\Feature\Authorization;

use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class V1QuotationPolicyTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    private function quotationFor(int $staffId, int $userId, QuotationStatus $status = QuotationStatus::Draft): Quotation
    {
        $opportunity = Opportunity::factory()->create(['assigned_staff_id' => $staffId]);

        return Quotation::factory()->forOpportunity($opportunity)->create([
            'assigned_staff_id' => $staffId,
            'created_by' => $userId,
            'status' => $status->value,
        ]);
    }

    public function test_sales_staff_can_operate_own_draft_and_sent_quotation_only(): void
    {
        [$sales, $staff] = $this->makeV1SalesStaff();
        [, $otherStaff] = $this->makeV1SalesStaff('Other Sales');
        $mine = $this->quotationFor($staff->id, $sales->id);
        $other = $this->quotationFor($otherStaff->id, $otherStaff->user_id ?? $sales->id);

        $this->assertTrue($sales->can('viewAny', Quotation::class));
        $this->assertTrue($sales->can('view', $mine));
        $this->assertTrue($sales->can('update', $mine));
        $this->assertFalse($sales->can('approve', $mine));
        $this->assertFalse($sales->can('view', $other));

        $mine->update(['status' => QuotationStatus::Approved->value]);
        $this->assertTrue($sales->can('send', $mine->fresh()));

        $mine->update(['status' => QuotationStatus::Sent->value]);
        $this->assertTrue($sales->can('recordCustomerResponse', $mine->fresh()));
        $this->assertFalse($sales->can('update', $mine->fresh()));
    }

    public function test_sales_manager_can_approve_other_creator_but_not_own_quotation(): void
    {
        [$manager, $managerStaff] = $this->makeV1SalesManager();
        [$sales, $salesStaff] = $this->makeV1SalesStaff();
        $staffQuote = $this->quotationFor($salesStaff->id, $sales->id, QuotationStatus::PendingApproval);
        $managerQuote = $this->quotationFor($managerStaff->id, $manager->id, QuotationStatus::PendingApproval);

        $this->assertTrue($manager->can('view', $staffQuote));
        $this->assertTrue($manager->can('approve', $staffQuote));
        $this->assertFalse($manager->can('approve', $managerQuote));
        $this->assertTrue($manager->can('cancel', $staffQuote));
    }

    public function test_finance_can_read_and_verify_payment_but_cannot_edit_sales_content(): void
    {
        [$sales, $staff] = $this->makeV1SalesStaff();
        [$finance] = $this->makeV1Finance();
        $quotation = $this->quotationFor($staff->id, $sales->id, QuotationStatus::Accepted);

        $this->assertTrue($finance->can('viewAny', Quotation::class));
        $this->assertTrue($finance->can('view', $quotation));
        $this->assertTrue($finance->can('verifyPayment', $quotation));
        $this->assertFalse($finance->can('update', $quotation));
        $this->assertFalse($finance->can('send', $quotation));
        $this->assertFalse($finance->can('approve', $quotation));
    }

    public function test_system_admin_can_audit_quotation_but_cannot_run_commercial_workflow(): void
    {
        [$sales, $staff] = $this->makeV1SalesStaff();
        $admin = $this->makeV1SystemAdmin();
        $quotation = $this->quotationFor($staff->id, $sales->id);

        $this->assertTrue($admin->can('view', $quotation));
        $this->assertFalse($admin->can('update', $quotation));
        $this->assertFalse($admin->can('send', $quotation));
        $this->assertFalse($admin->can('approve', $quotation));
        $this->assertFalse($admin->can('verifyPayment', $quotation));
    }

    public function test_customer_care_and_pure_marketing_cannot_access_quotation(): void
    {
        [$sales, $staff] = $this->makeV1SalesStaff();
        $quotation = $this->quotationFor($staff->id, $sales->id);

        foreach ([$this->makeV1CustomerCareStaff()[0], $this->makeV1MarketingStaff()[0]] as $actor) {
            $this->assertFalse($actor->can('viewAny', Quotation::class));
            $this->assertFalse($actor->can('view', $quotation));
        }
    }
}
