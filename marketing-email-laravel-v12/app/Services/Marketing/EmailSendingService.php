<?php

namespace App\Services\Marketing;

use App\Enums\Marketing\CampaignRecipientStatus;
use App\Enums\Marketing\EmailEventType;
use App\Mail\MarketingCampaignMail;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\CampaignRecipient;
use App\Models\Marketing\EmailEvent;
use App\Models\Marketing\SendingAccount;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailSendingService
{
    public function __construct(
        private readonly TemplateRenderService $templateRender,
        private readonly TrackingLinkService $trackingLink,
        private readonly SuppressionService $suppression,
        private readonly CampaignLandingPageService $campaignLandingPageService,
    ) {}

    /**
     * Send a campaign email to one recipient.
     */
    public function sendToRecipient(CampaignRecipient $recipient): void
    {
        $campaign = $recipient->campaign()->with(['template', 'sendingAccount', 'landingPage'])->first();

        if (! $campaign || ! $campaign->template || ! $campaign->sendingAccount) {
            $this->markFailed($recipient, 'Campaign, template or sending account not found.');

            return;
        }

        $contact = $recipient->contact;

        if (! $contact || blank($contact->email)) {
            $this->markFailed($recipient, 'Contact has no email.');

            return;
        }

        if ($this->suppression->isSuppressed($contact->email)) {
            $this->markSkipped($recipient, 'Email is suppressed.');

            return;
        }

        $unsubscribeUrl = route('marketing.unsubscribe.show', ['token' => $recipient->unsubscribe_token]);

        // Build extra placeholders, including landing_page_url if campaign has a landing page
        $extraPlaceholders = [];
        if ($campaign->landing_page_id && $campaign->landingPage) {
            $extraPlaceholders['landing_page_url'] = $this->campaignLandingPageService->getCampaignLink($campaign);
        }

        $rendered = $this->templateRender->render(
            template: $campaign->template,
            contact: $contact,
            unsubscribeUrl: $unsubscribeUrl,
            extraPlaceholders: $extraPlaceholders,
        );

        $htmlWithTracking = $this->trackingLink->processHtml(
            $rendered['html_body'],
            $recipient
        );

        try {
            $this->configureMailer($campaign->sendingAccount);

            Mail::to($contact->email)
                ->send(new MarketingCampaignMail(
                    subjectLine: $rendered['subject'],
                    preheader: $rendered['preheader'],
                    htmlBody: $htmlWithTracking,
                    textBody: $rendered['text_body'],
                    fromAddress: $campaign->sendingAccount->from_email,
                    fromName: $campaign->sendingAccount->from_name,
                ));

            $recipient->update([
                'status' => CampaignRecipientStatus::Sent->value,
                'sent_at' => now(),
            ]);

            EmailEvent::create([
                'campaign_id' => $campaign->id,
                'campaign_recipient_id' => $recipient->id,
                'contact_id' => $recipient->contact_id,
                'event_type' => EmailEventType::Sent->value,
                'event_payload' => [],
                'occurred_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('EmailSendingService: failed to send', [
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($recipient, $e->getMessage());
        }
    }

    /**
     * Configure Laravel mailer from SendingAccount credentials.
     */
    public function configureMailer(SendingAccount $account): void
    {
        $config = $account->config_encrypted ?? [];

        if ($account->provider === 'smtp' && filled($config['host'] ?? null)) {
            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => $config['host'],
                'port' => $config['port'] ?? 587,
                'encryption' => $config['encryption'] ?? 'tls',
                'username' => $config['username'] ?? null,
                'password' => $config['password'] ?? null,
            ]);
            Config::set('mail.default', 'smtp');
        }

        if (filled($account->from_email)) {
            Config::set('mail.from.address', $account->from_email);
            Config::set('mail.from.name', $account->from_name ?? config('app.name'));
        }
    }

    /**
     * Configure a dedicated SMTP mailer (e.g. for customer-care sending) without
     * touching the default mailer used by campaign dispatch.
     */
    public function configureSmtpMailer(string $mailer, array $config, ?string $fromAddress = null, ?string $fromName = null): void
    {
        Config::set("mail.mailers.{$mailer}", [
            'transport' => 'smtp',
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? 587,
            'encryption' => $config['encryption'] ?? 'tls',
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
        ]);

        if (filled($fromAddress)) {
            Config::set("mail.mailers.{$mailer}.from", [
                'address' => $fromAddress,
                'name' => $fromName ?? config('app.name'),
            ]);
        }
    }

    private function markFailed(CampaignRecipient $recipient, string $reason): void
    {
        $recipient->update([
            'status' => CampaignRecipientStatus::Failed->value,
        ]);

        EmailEvent::create([
            'campaign_id' => $recipient->campaign_id,
            'campaign_recipient_id' => $recipient->id,
            'contact_id' => $recipient->contact_id,
            'event_type' => EmailEventType::Failed->value,
            'event_payload' => ['reason' => $reason],
            'occurred_at' => now(),
        ]);
    }

    private function markSkipped(CampaignRecipient $recipient, string $reason): void
    {
        $recipient->update([
            'status' => CampaignRecipientStatus::Skipped->value,
        ]);

        EmailEvent::create([
            'campaign_id' => $recipient->campaign_id,
            'campaign_recipient_id' => $recipient->id,
            'contact_id' => $recipient->contact_id,
            'event_type' => EmailEventType::Skipped->value,
            'event_payload' => ['reason' => $reason],
            'occurred_at' => now(),
        ]);
    }
}
