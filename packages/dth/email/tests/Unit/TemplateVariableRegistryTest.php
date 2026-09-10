<?php

namespace Dth\Email\Tests\Unit;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Services\TemplateVariableRegistry;
use Dth\Email\Tests\TestCase;

class TemplateVariableRegistryTest extends TestCase
{
    public function test_it_detects_custom_recipient_requirements_and_csv_columns(): void
    {
        $campaign = new EmailCampaign([
            'status' => EmailCampaignStatus::Draft,
            'subject' => 'Hello {{ name }}',
            'html_body' => '<p>{{ package.name }} {{ email }} {{ unsubscribe_url }}</p>',
        ]);

        $registry = new TemplateVariableRegistry();

        $this->assertSame([
            'name' => 'Recipient name',
            'package.name' => 'Package Name',
        ], $registry->recipientRequirements($campaign));

        $this->assertSame([
            'email',
            'name',
            'package.name',
        ], $registry->expectedCsvColumns($campaign));
    }
}
