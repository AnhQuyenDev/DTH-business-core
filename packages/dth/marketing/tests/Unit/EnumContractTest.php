<?php

namespace Dth\Marketing\Tests\Unit;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Enums\LandingPageContactAction;
use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Enums\LandingPageSubmissionStatus;
use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Enums\SemanticFieldRole;
use PHPUnit\Framework\TestCase;

class EnumContractTest extends TestCase
{
    public function test_foundation_enum_values_match_the_approved_business_lifecycle(): void
    {
        $this->assertSame(['draft', 'active', 'paused', 'completed', 'cancelled'], MarketingCampaignStatus::values());
        $this->assertSame(['draft', 'published', 'archived'], LandingPageStatus::values());
        $this->assertSame(['draft', 'active', 'archived'], FormTemplateStatus::values());
        $this->assertSame(['received', 'processed', 'failed', 'spam'], LandingPageSubmissionStatus::values());
        $this->assertSame(['created', 'updated', 'skipped'], LandingPageContactAction::values());
        $this->assertSame(['personal', 'business'], FormAudienceType::values());
        $this->assertSame(['text', 'email', 'phone', 'textarea', 'select', 'checkbox', 'hidden'], FormFieldType::values());
        $this->assertContains('person.name', SemanticFieldRole::values());
        $this->assertContains('service.interest', SemanticFieldRole::values());
    }
}
