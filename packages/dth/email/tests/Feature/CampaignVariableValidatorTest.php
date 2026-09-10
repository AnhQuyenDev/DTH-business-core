<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Enums\EmailTemplateStatus;
use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Models\EmailTemplate;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\CampaignRecipientService;
use Dth\Email\Services\CampaignVariableValidator;
use Dth\Email\Tests\TestCase;
use LogicException;

class CampaignVariableValidatorTest extends TestCase
{
    public function test_custom_variables_are_required_before_send(): void
    {
        $campaign = $this->campaign('<p>Hello {{ name }} - {{ package.name }}</p>');

        $this->app->make(CampaignRecipientService::class)->add($campaign, [
            'email' => 'person@example.com',
            'name' => 'Person',
        ]);

        $validator = $this->app->make(CampaignVariableValidator::class);

        try {
            $validator->assertRecipientsComplete($campaign);
            $this->fail('Expected missing personalization data to be rejected.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('{{ package.name }}', $e->getMessage());
        }

        $campaign->recipients()->firstOrFail()->update([
            'variables' => [
                'package' => ['name' => 'Premium'],
            ],
        ]);

        $validator->assertRecipientsComplete($campaign);
        $this->addToAssertionCount(1);
    }

    private function campaign(string $html): EmailCampaign
    {
        $template = EmailTemplate::query()->create([
            'name' => 'Variable template',
            'subject' => 'Hello',
            'html_body' => $html,
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
            'html_body' => $html,
        ]);
    }
}
