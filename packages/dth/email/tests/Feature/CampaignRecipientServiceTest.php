<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Enums\EmailTemplateStatus;
use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Models\EmailTemplate;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\CampaignRecipientService;
use Dth\Email\Tests\TestCase;
use LogicException;

class CampaignRecipientServiceTest extends TestCase
{
    public function test_recipients_can_only_be_added_while_campaign_is_draft(): void
    {
        $campaign = $this->campaign();
        $service = $this->app->make(CampaignRecipientService::class);

        $service->add($campaign, [
            'email' => 'first@example.com',
            'name' => 'First',
        ]);

        $campaign->update([
            'status' => EmailCampaignStatus::Completed,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Draft');

        $service->add($campaign, [
            'email' => 'second@example.com',
            'name' => 'Second',
        ]);
    }

    private function campaign(): EmailCampaign
    {
        $template = EmailTemplate::query()->create([
            'name' => 'Test template',
            'subject' => 'Hello',
            'html_body' => '<p>Hello</p>',
            'status' => EmailTemplateStatus::Active,
        ]);

        $account = SendingAccount::query()->create([
            'name' => 'SMTP',
            'provider' => 'smtp',
            'from_name' => 'DTH',
            'from_email' => 'sender@example.com',
            'encrypted_config' => [],
            'status' => SendingAccountStatus::Active,
        ]);

        return EmailCampaign::query()->create([
            'name' => 'Campaign',
            'subject' => 'Hello',
            'email_template_id' => $template->id,
            'sending_account_id' => $account->id,
            'status' => EmailCampaignStatus::Draft,
            'html_body' => '<p>Hello</p>',
        ]);
    }
}
