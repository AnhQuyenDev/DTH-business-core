<?php

namespace Dth\Email\Tests\Unit;

use Dth\Email\Services\TransportCapabilityService;
use Dth\Email\Tests\TestCase;

class TransportCapabilityServiceTest extends TestCase
{
    public function test_smtp_reports_provider_events_as_unavailable(): void
    {
        $capabilities = app(TransportCapabilityService::class)->forProvider('smtp');

        $this->assertTrue($capabilities->sent);
        $this->assertTrue($capabilities->open);
        $this->assertTrue($capabilities->click);
        $this->assertTrue($capabilities->unsubscribe);
        $this->assertFalse($capabilities->delivery);
        $this->assertFalse($capabilities->bounce);
        $this->assertFalse($capabilities->complaint);
    }

    public function test_tracking_switch_disables_open_and_click_capabilities(): void
    {
        config()->set('dth-email.tracking.enabled', false);

        $capabilities = app(TransportCapabilityService::class)->forProvider('smtp');

        $this->assertFalse($capabilities->open);
        $this->assertFalse($capabilities->click);
        $this->assertTrue($capabilities->unsubscribe);
    }
}
