<?php

namespace Dth\Marketing\Tests\Unit;

use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Filament\Support\StatusColor;
use PHPUnit\Framework\TestCase;

class StatusColorTest extends TestCase
{
    public function test_status_colors_use_raw_business_statuses(): void
    {
        $this->assertSame('gray', StatusColor::for(MarketingCampaignStatus::Draft));
        $this->assertSame('success', StatusColor::for(MarketingCampaignStatus::Active));
        $this->assertSame('warning', StatusColor::for(MarketingCampaignStatus::Paused));
        $this->assertSame('success', StatusColor::for(MarketingCampaignStatus::Completed));
        $this->assertSame('gray', StatusColor::for(MarketingCampaignStatus::Cancelled));
        $this->assertSame('success', StatusColor::for(LandingPageStatus::Published));
        $this->assertSame('danger', StatusColor::for('spam'));
        $this->assertSame('gray', StatusColor::for('unmapped-status'));
    }
}
