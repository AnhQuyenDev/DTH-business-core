<?php

namespace Tests\Feature;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Marketing\CampaignRecipientStatus;
use App\Enums\Marketing\CampaignStatus;
use App\Jobs\Marketing\PrepareCampaignRecipientsJob;
use App\Mail\MarketingCampaignMail;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\Contact;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\SendingAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CampaignFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_campaign_recipients_creates_recipient_records(): void
    {
        Mail::fake();

        $contact = Contact::query()->create(['contact_type' => 'personal']);

        $customer = Customer::query()->create([
            'customer_code'  => 'CUS-MAI-TRAN',
            'contact_id'     => $contact->id,
            'customer_type'  => 'personal',
            'display_name'   => 'Mai Tran',
            'first_name'     => 'Mai',
            'last_name'      => 'Tran',
            'email'          => 'mai@example.test',
            'consent_status' => CustomerConsentStatus::Subscribed,
            'status'         => CustomerStatus::Active,
        ]);

        $template = EmailTemplate::query()->create([
            'name' => 'Campaign Template',
            'subject' => 'Hello {{first_name}}',
            'preheader' => 'Demo',
            'html_body' => '<p>Hello {{first_name}}</p><p><a href="https://example.test">Visit</a></p>',
            'text_body' => 'Hello {{first_name}}',
            'status' => 'active',
            'created_by' => null,
        ]);

        $sendingAccount = SendingAccount::query()->create([
            'name' => 'Demo Account',
            'provider' => 'laravel_mail',
            'from_name' => 'Demo',
            'from_email' => 'demo@example.test',
            'reply_to' => 'reply@example.test',
            'config_encrypted' => ['mailer' => 'log'],
            'daily_limit' => 1000,
            'hourly_limit' => 100,
            'status' => 'active',
        ]);

        $campaign = Campaign::query()->create([
            'name' => 'Demo Campaign',
            'subject' => 'Demo Campaign',
            'preheader' => 'Demo',
            'email_template_id' => $template->id,
            'sending_account_id' => $sendingAccount->id,
            'audience_type' => 'all_subscribed',
            'audience_id' => null,
            'status' => CampaignStatus::Sending->value,
            'created_by' => null,
        ]);

        app()->call([new PrepareCampaignRecipientsJob($campaign->id), 'handle']);

        $campaign->refresh();

        $this->assertSame(CampaignStatus::Sending->value, $campaign->status);
        $this->assertDatabaseCount('campaign_recipients', 1);
        $this->assertDatabaseHas('campaign_recipients', [
            'campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'status' => CampaignRecipientStatus::Sent->value,
        ]);
        Mail::assertSent(MarketingCampaignMail::class, 1);
    }

    public function test_prepare_campaign_recipients_only_uses_qualified_customers_from_selected_landing_page(): void
    {
        Mail::fake();

        $qualifiedContact = Contact::query()->create(['contact_type' => 'personal']);
        $unqualifiedContact = Contact::query()->create(['contact_type' => 'personal']);

        $qualifiedCustomer = Customer::query()->create([
            'customer_code' => 'CUS-QUALIFIED',
            'contact_id' => $qualifiedContact->id,
            'customer_type' => 'personal',
            'display_name' => 'Qualified Lead',
            'first_name' => 'Qualified',
            'last_name' => 'Lead',
            'email' => 'qualified@example.test',
            'consent_status' => CustomerConsentStatus::Subscribed,
            'status' => CustomerStatus::Active,
        ]);

        Customer::query()->create([
            'customer_code' => 'CUS-UNQUALIFIED',
            'contact_id' => $unqualifiedContact->id,
            'customer_type' => 'personal',
            'display_name' => 'Unqualified Lead',
            'first_name' => 'Unqualified',
            'last_name' => 'Lead',
            'email' => 'unqualified@example.test',
            'consent_status' => CustomerConsentStatus::Subscribed,
            'status' => CustomerStatus::Active,
        ]);

        ContactQualification::query()->create([
            'contact_id' => $qualifiedContact->id,
            'status' => ContactQualificationStatus::Qualified,
        ]);

        ContactQualification::query()->create([
            'contact_id' => $unqualifiedContact->id,
            'status' => ContactQualificationStatus::Unqualified,
        ]);

        $landingPage = LandingPage::query()->create([
            'name' => 'Qualified Landing Page',
            'slug' => 'qualified-landing-page',
            'status' => 'published',
        ]);

        LandingPageSubmission::query()->create([
            'landing_page_id' => $landingPage->id,
            'contact_id' => $qualifiedContact->id,
            'data' => ['email' => 'qualified@example.test'],
            'normalized_email' => 'qualified@example.test',
            'status' => 'processed',
            'submitted_at' => now(),
        ]);

        LandingPageSubmission::query()->create([
            'landing_page_id' => $landingPage->id,
            'contact_id' => $unqualifiedContact->id,
            'data' => ['email' => 'unqualified@example.test'],
            'normalized_email' => 'unqualified@example.test',
            'status' => 'processed',
            'submitted_at' => now(),
        ]);

        $template = EmailTemplate::query()->create([
            'name' => 'Campaign Template',
            'subject' => 'Hello {{first_name}}',
            'preheader' => 'Demo',
            'html_body' => '<p>Hello {{first_name}}</p>',
            'text_body' => 'Hello {{first_name}}',
            'status' => 'active',
            'created_by' => null,
        ]);

        $sendingAccount = SendingAccount::query()->create([
            'name' => 'Demo Account',
            'provider' => 'laravel_mail',
            'from_name' => 'Demo',
            'from_email' => 'demo@example.test',
            'reply_to' => 'reply@example.test',
            'config_encrypted' => ['mailer' => 'log'],
            'daily_limit' => 1000,
            'hourly_limit' => 100,
            'status' => 'active',
        ]);

        $campaign = Campaign::query()->create([
            'name' => 'Qualified Campaign',
            'subject' => 'Qualified Campaign',
            'preheader' => 'Demo',
            'email_template_id' => $template->id,
            'sending_account_id' => $sendingAccount->id,
            'landing_page_id' => $landingPage->id,
            'audience_type' => 'qualified',
            'audience_id' => null,
            'status' => CampaignStatus::Sending->value,
            'created_by' => null,
        ]);

        app()->call([new PrepareCampaignRecipientsJob($campaign->id), 'handle']);

        $this->assertDatabaseCount('campaign_recipients', 1);
        $this->assertDatabaseHas('campaign_recipients', [
            'campaign_id' => $campaign->id,
            'customer_id' => $qualifiedCustomer->id,
            'email' => 'qualified@example.test',
        ]);
        $this->assertDatabaseMissing('campaign_recipients', [
            'campaign_id' => $campaign->id,
            'email' => 'unqualified@example.test',
        ]);
    }
}