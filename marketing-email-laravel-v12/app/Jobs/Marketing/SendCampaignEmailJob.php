<?php

namespace App\Jobs\Marketing;

use App\Enums\Marketing\CampaignRecipientStatus;
use App\Enums\Marketing\CampaignStatus;
use App\Enums\Marketing\EmailEventType;
use App\Mail\MarketingCampaignMail;
use App\Models\Crm\Customer;
use App\Models\Marketing\CampaignRecipient;
use App\Models\Marketing\EmailEvent;
use App\Services\Marketing\TrackingLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $campaignRecipientId)
    {
    }

    public function handle(TrackingLinkService $trackingLinkService): void
    {
        $recipient = CampaignRecipient::query()->with(['campaign.template', 'customer'])->findOrFail($this->campaignRecipientId);
        $campaign = $recipient->campaign;
        $template = $campaign?->template;
        $customer = $recipient->customer;

        if (! $campaign || ! $template || ! $customer) {
            $recipient->update([
                'status' => CampaignRecipientStatus::Failed->value,
                'failed_at' => now(),
                'failure_reason' => __('notification.missing_campaign_template_or_customer'),
            ]);

            $this->finalizeCampaign($recipient->campaign_id);

            return;
        }

        try {
            $htmlBody = $recipient->personalized_html ?: $template->html_body;
            $htmlBody = $trackingLinkService->processHtml($htmlBody, $recipient);

            Mail::to($recipient->email)->send(new MarketingCampaignMail(
                subjectLine: $recipient->personalized_subject ?: $template->subject,
                preheader: $template->preheader,
                htmlBody: $htmlBody,
                textBody: $template->text_body,
                fromAddress: $campaign->sendingAccount?->from_email,
                fromName: $campaign->sendingAccount?->from_name,
            ));

            $recipient->update([
                'status' => CampaignRecipientStatus::Sent->value,
                'sent_at' => now(),
                'failure_reason' => null,
            ]);

            EmailEvent::query()->create([
                'campaign_id' => $campaign->id,
                'campaign_recipient_id' => $recipient->id,
                'customer_id' => $customer->id,
                'event_type' => EmailEventType::Sent->value,
                'event_payload' => [
                    'email' => $recipient->email,
                ],
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $throwable) {
            $recipient->update([
                'status' => CampaignRecipientStatus::Failed->value,
                'failed_at' => now(),
                'failure_reason' => $throwable->getMessage(),
            ]);

            EmailEvent::query()->create([
                'campaign_id' => $campaign->id,
                'campaign_recipient_id' => $recipient->id,
                'customer_id' => $customer->id,
                'event_type' => EmailEventType::Failed->value,
                'event_payload' => [
                    'email' => $recipient->email,
                    'error' => $throwable->getMessage(),
                ],
                'occurred_at' => now(),
            ]);
        }

        $this->finalizeCampaign($campaign->id);
    }

    protected function finalizeCampaign(int $campaignId): void
    {
        $remaining = CampaignRecipient::query()
            ->where('campaign_id', $campaignId)
            ->whereIn('status', [CampaignRecipientStatus::Pending->value, CampaignRecipientStatus::Queued->value])
            ->exists();

        if ($remaining) {
            return;
        }

        $failedCount = CampaignRecipient::query()
            ->where('campaign_id', $campaignId)
            ->where('status', CampaignRecipientStatus::Failed->value)
            ->count();

        $sentCount = CampaignRecipient::query()
            ->where('campaign_id', $campaignId)
            ->where('status', CampaignRecipientStatus::Sent->value)
            ->count();

        $campaign = \App\Models\Marketing\Campaign::query()->find($campaignId);

        if ($campaign) {
            $campaign->update([
                'status' => $sentCount > 0 && $failedCount === 0 ? CampaignStatus::Sent->value : CampaignStatus::Failed->value,
                'sent_at' => now(),
            ]);
        }
    }
}