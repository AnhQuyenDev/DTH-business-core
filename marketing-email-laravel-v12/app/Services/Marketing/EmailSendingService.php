<?php

namespace App\Services\Marketing;

use App\Enums\Marketing\CampaignRecipientStatus;
use App\Enums\Marketing\EmailEventType;
use App\Mail\MarketingCampaignMail;
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
        private readonly SendingAccountMailerService $sendingAccountMailer,
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
            $this->sendingAccountMailer->send(
                account: $campaign->sendingAccount,
                recipientEmail: $contact->email,
                mailable: new MarketingCampaignMail(
                    subjectLine: $rendered['subject'],
                    preheader: $rendered['preheader'],
                    htmlBody: $htmlWithTracking,
                    textBody: $rendered['text_body'],
                    fromAddress: $campaign->sendingAccount->from_email,
                    fromName: $campaign->sendingAccount->from_name,
                ),
            );

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
                'sending_account_id' => $campaign->sending_account_id,
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($recipient, $e->getMessage());
        }
    }

    /**
     * Legacy compatibility helper.
     *
     * New code should call SendingAccountMailerService::send() directly. This
     * method remains so older integrations do not break during deployment.
     */
    public function configureMailer(SendingAccount $account): void
    {
        if ($account->provider === 'smtp') {
            $mailerName = $this->sendingAccountMailer->prepareMailer($account);
            Config::set('mail.default', $mailerName);

            return;
        }

        $this->sendingAccountMailer->assertUsable($account);
    }

    /**
     * Legacy compatibility helper for older call sites.
     *
     * Prefer SendingAccountMailerService for all new sends.
     */
    public function configureSmtpMailer(string $mailer, array $config, ?string $fromAddress = null, ?string $fromName = null): void
    {
        $encryption = strtolower(trim((string) ($config['encryption'] ?? $config['scheme'] ?? 'tls')));
        $scheme = in_array($encryption, ['ssl', 'smtps'], true) ? 'smtps' : 'smtp';

        Mail::purge($mailer);

        Config::set("mail.mailers.{$mailer}", array_filter([
            'transport' => 'smtp',
            'scheme' => $scheme,
            'host' => isset($config['host']) ? trim((string) $config['host']) : null,
            'port' => (int) ($config['port'] ?? ($scheme === 'smtps' ? 465 : 587)),
            'username' => isset($config['username']) ? trim((string) $config['username']) : null,
            'password' => $config['password'] ?? null,
            'timeout' => isset($config['timeout']) ? (int) $config['timeout'] : null,
            'local_domain' => $config['local_domain']
                ?? parse_url((string) config('app.url'), PHP_URL_HOST),
        ], static fn ($value): bool => $value !== null && $value !== ''));

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
