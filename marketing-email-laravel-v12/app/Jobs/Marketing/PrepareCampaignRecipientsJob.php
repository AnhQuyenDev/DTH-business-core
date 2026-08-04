<?php

namespace App\Jobs\Marketing;

use App\Enums\Marketing\CampaignRecipientStatus;
use App\Enums\Marketing\CampaignStatus;
use App\Enums\Marketing\EmailEventType;
use App\Models\Crm\Customer;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\CampaignRecipient;
use App\Models\Marketing\EmailEvent;
use App\Models\Marketing\SuppressionEntry;
use App\Services\Marketing\CampaignAudienceService;
use App\Services\Marketing\CampaignLandingPageService;
use App\Services\Marketing\TemplateRenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PrepareCampaignRecipientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId)
    {
    }

    public function handle(
        CampaignAudienceService $audienceService,
        TemplateRenderService $templateRenderService,
        CampaignLandingPageService $campaignLandingPageService
    ): void
    {
        $campaign = Campaign::query()->with(['template', 'sendingAccount', 'landingPage'])->findOrFail($this->campaignId);

        if (! in_array($campaign->status, [CampaignStatus::Preparing->value, CampaignStatus::Sending->value, CampaignStatus::Scheduled->value], true)) {
            return;
        }

        $template = $campaign->template;

        if (! $template) {
            $campaign->update(['status' => CampaignStatus::Failed->value]);

            return;
        }

        $campaign->update(['status' => CampaignStatus::Preparing->value]);

        $audience = $audienceService->recipientsForCampaign($campaign);
        $dispatchedCount = 0;
        $seenEmails = [];

        foreach ($audience as $customer) {
            /** @var Customer $customer */
            $email = strtolower(trim((string) $customer->email));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->createSkippedRecipient($campaign->id, $customer->id, $customer->contact_id, $email, 'Invalid email address.');

                continue;
            }

            if (SuppressionEntry::query()->whereRaw('lower(email) = ?', [$email])->exists()) {
                $this->createSkippedRecipient($campaign->id, $customer->id, $customer->contact_id, $email, 'Email exists in suppression list.');

                continue;
            }

            if (in_array($email, $seenEmails, true)) {
                $this->createSkippedRecipient($campaign->id, $customer->id, $customer->contact_id, $email, 'Duplicate email in audience.');

                continue;
            }

            $seenEmails[] = $email;

            if (CampaignRecipient::query()->where('campaign_id', $campaign->id)->where('customer_id', $customer->id)->exists()) {
                $this->createSkippedRecipient($campaign->id, $customer->id, $customer->contact_id, $email, 'Recipient already exists for this campaign.');

                continue;
            }

            $recipient = CampaignRecipient::query()->create([
                'campaign_id' => $campaign->id,
                'contact_id' => $customer->contact_id,
                'customer_id' => $customer->id,
                'email' => $email,
                'status' => CampaignRecipientStatus::Queued->value,
                'unsubscribe_token' => Str::uuid()->toString(),
                'tracking_token' => Str::uuid()->toString(),
            ]);

            EmailEvent::query()->create([
                'campaign_id' => $campaign->id,
                'campaign_recipient_id' => $recipient->id,
                'customer_id' => $customer->id,
                'event_type' => EmailEventType::Queued->value,
                'event_payload' => [
                    'email' => $email,
                ],
                'occurred_at' => now(),
            ]);

            $extraPlaceholders = [];
            if ($campaign->landingPage) {
                $extraPlaceholders['landing_page_url'] = $campaignLandingPageService->getCampaignLink($campaign);
            }

            $rendered = $templateRenderService->render(
                template: $template,
                customer: $customer,
                unsubscribeUrl: url('/m/unsubscribe/' . $recipient->unsubscribe_token),
                subjectOverride: $campaign->subject,
                extraPlaceholders: $extraPlaceholders,
            );

            $recipient->update([
                'personalized_subject' => $rendered['subject'],
                'personalized_html' => $rendered['html_body'],
                'status' => CampaignRecipientStatus::Queued->value,
            ]);

            dispatch(new SendCampaignEmailJob($recipient->id))->onQueue('marketing');
            $dispatchedCount++;
        }

        if ($dispatchedCount === 0) {
            $campaign->update([
                'status' => CampaignRecipient::query()->where('campaign_id', $campaign->id)->exists()
                    ? CampaignStatus::Sent->value
                    : CampaignStatus::Failed->value,
                'sent_at' => now(),
            ]);
        } else {
            $campaign->update(['status' => CampaignStatus::Sending->value]);
        }
    }

    protected function createSkippedRecipient(int $campaignId, int $customerId, ?int $contactId, string $email, string $reason): void
    {
        CampaignRecipient::query()->create([
            'campaign_id' => $campaignId,
            'contact_id' => $contactId,
            'customer_id' => $customerId,
            'email' => $email,
            'status' => CampaignRecipientStatus::Skipped->value,
            'failure_reason' => $reason,
            'unsubscribe_token' => Str::uuid()->toString(),
            'tracking_token' => Str::uuid()->toString(),
        ]);
    }
}