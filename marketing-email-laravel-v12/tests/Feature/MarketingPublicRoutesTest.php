<?php

namespace Tests\Feature;

use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Marketing\CampaignRecipientStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\PersonalContactProfile;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\CampaignRecipient;
use App\Models\Marketing\Contact;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\SendingAccount;
use App\Models\Marketing\TrackedLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingPublicRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function makeContact(string $email, string $firstName, string $lastName): array
    {
        $contact = Contact::query()->create(['contact_type' => 'personal']);

        PersonalContactProfile::query()->create([
            'contact_id' => $contact->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]);

        $customer = Customer::query()->create([
            'customer_code' => 'CUS-'.strtoupper($firstName.$lastName),
            'contact_id' => $contact->id,
            'customer_type' => 'personal',
            'display_name' => $firstName.' '.$lastName,
            'email' => $email,
            'consent_status' => CustomerConsentStatus::Subscribed,
            'status' => CustomerStatus::Active,
        ]);

        return [$contact, $customer];
    }

    public function test_unsubscribe_route_updates_contact_and_suppression(): void
    {
        [$contact, $customer] = $this->makeContact('lan@example.test', 'Lan', 'Pham');

        $template = EmailTemplate::query()->create([
            'name' => 'Unsubscribe Template',
            'subject' => 'Hello',
            'preheader' => 'Demo',
            'html_body' => '<p>Hello</p>',
            'text_body' => 'Hello',
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
            'name' => 'Unsubscribe Campaign',
            'subject' => 'Demo',
            'preheader' => 'Demo',
            'email_template_id' => $template->id,
            'sending_account_id' => $sendingAccount->id,
            'audience_type' => 'all_subscribed',
            'status' => 'draft',
            'created_by' => null,
        ]);

        $recipient = CampaignRecipient::query()->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'status' => CampaignRecipientStatus::Sent->value,
            'unsubscribe_token' => 'unsubscribe-token',
            'tracking_token' => 'tracking-token',
        ]);

        $this->withHeader('Accept-Language', 'vi')
            ->post('/m/unsubscribe/unsubscribe-token')
            ->assertStatus(200)
            ->assertSee(__('unsubscribe.success_title'));

        $customer->refresh();
        $recipient->refresh();

        $this->assertSame(CustomerConsentStatus::Unsubscribed->value, $customer->consent_status->value);
        $this->assertNotNull($customer->unsubscribed_at);
        $this->assertSame(CampaignRecipientStatus::Unsubscribed->value, $recipient->status);
        $this->assertDatabaseHas('suppression_entries', [
            'email' => 'lan@example.test',
            'reason' => 'unsubscribe',
        ]);
    }

    public function test_click_tracking_redirects_to_original_url(): void
    {
        [$contact, $customer] = $this->makeContact('quynh@example.test', 'Quynh', 'Le');

        $template = EmailTemplate::query()->create([
            'name' => 'Click Template',
            'subject' => 'Demo',
            'preheader' => 'Demo',
            'html_body' => '<p><a href="https://example.test/offer">Offer</a></p>',
            'text_body' => 'Demo',
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
            'name' => 'Tracking Campaign',
            'subject' => 'Demo',
            'preheader' => 'Demo',
            'email_template_id' => $template->id,
            'sending_account_id' => $sendingAccount->id,
            'audience_type' => 'all_subscribed',
            'status' => 'draft',
            'created_by' => null,
        ]);

        $recipient = CampaignRecipient::query()->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'status' => CampaignRecipientStatus::Sent->value,
            'unsubscribe_token' => 'unsubscribe-token-2',
            'tracking_token' => 'tracking-token-2',
        ]);

        $trackedLink = TrackedLink::query()->create([
            'campaign_id' => $campaign->id,
            'campaign_recipient_id' => $recipient->id,
            'original_url' => 'https://example.test/offer',
            'tracking_token' => 'click-token',
            'click_count' => 0,
        ]);

        $this->get('/m/click/click-token')
            ->assertRedirect('https://example.test/offer');

        $trackedLink->refresh();

        $this->assertSame(1, $trackedLink->click_count);
        $this->assertNotNull($trackedLink->last_clicked_at);
        $this->assertDatabaseHas('email_events', [
            'campaign_id' => $campaign->id,
            'campaign_recipient_id' => $recipient->id,
            'event_type' => 'clicked',
        ]);
    }
}
