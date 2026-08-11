<?php

namespace Tests\Feature\V1;

use App\Models\CompanySetting;
use App\Services\Business\WorkflowPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowPolicyDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_v1_feature_flags_are_enabled_in_the_test_baseline(): void
    {
        $this->assertTrue((bool) config('business_flow.v2_enabled'));
        $this->assertTrue((bool) config('business_flow.company_resolution_enabled'));
        $this->assertTrue((bool) config('business_flow.opportunity_quotation_enabled'));
        $this->assertTrue((bool) config('business_flow.customer_on_paid_only'));
    }

    public function test_canonical_v1_customer_confirmation_is_click_without_otp(): void
    {
        $settings = CompanySetting::firstOrCreateDefault();
        $this->assertSame('click', $settings->quotation_confirmation_mode);
    }

    public function test_payment_evidence_is_optional_by_default_but_remains_policy_driven(): void
    {
        $settings = CompanySetting::firstOrCreateDefault();
        $this->assertFalse($settings->payment_evidence_required);

        $settings->update(['payment_evidence_required' => true]);
        $this->assertTrue(app(WorkflowPolicyService::class)->paymentEvidenceRequired());
    }

    public function test_default_approval_policy_uses_amount_or_discount_thresholds(): void
    {
        $settings = CompanySetting::firstOrCreateDefault();

        $this->assertSame('amount_or_discount', $settings->quotation_approval_mode);
        $this->assertSame(20_000_000.0, (float) $settings->quotation_approval_amount_threshold);
        $this->assertSame(10.0, (float) $settings->quotation_approval_discount_threshold_percent);
    }
}
