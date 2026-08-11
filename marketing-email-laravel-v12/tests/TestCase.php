<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Canonical DTH Business Core V1 baseline. Individual tests may
        // override a policy explicitly when testing an extension point.
        config()->set([
            'business_flow.v2_enabled' => true,
            'business_flow.company_resolution_enabled' => true,
            'business_flow.opportunity_quotation_enabled' => true,
            'business_flow.customer_on_paid_only' => true,
            'business_flow.quotation_email_queue_enabled' => false,
            'v1_workflow.quotation_approval.mode' => 'amount_or_discount',
            'v1_workflow.quotation_approval.amount_threshold' => 20_000_000,
            'v1_workflow.quotation_approval.discount_threshold_percent' => 10,
            'v1_workflow.customer_confirmation.mode' => 'click',
            'v1_workflow.payment_notice.evidence_required' => false,
        ]);

        app()->setLocale('vi');
    }
}
