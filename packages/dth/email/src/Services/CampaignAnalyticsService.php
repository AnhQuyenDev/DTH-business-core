<?php

namespace Dth\Email\Services;

use Dth\Email\DTO\CampaignAnalyticsResult;
use Dth\Email\Enums\CampaignRecipientStatus;
use Dth\Email\Enums\EmailEventType;
use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Models\EmailCampaign;
use Illuminate\Support\Facades\DB;

class CampaignAnalyticsService
{
    public function __construct(
        private readonly TransportCapabilityService $capabilities,
    ) {}

    public function forCampaignId(int $campaignId): CampaignAnalyticsResult
    {
        return $this->forCampaign(
            EmailCampaign::query()->findOrFail($campaignId)
        );
    }

    public function forCampaign(EmailCampaign $campaign): CampaignAnalyticsResult
    {
        $campaign->loadMissing('sendingAccount');

        $capabilities = $this->capabilities->forProvider(
            $campaign->sendingAccount?->provider ?? 'smtp'
        );

        $metrics = DB::table('email_campaign_recipients as ecr')
            ->leftJoin('email_messages as em', 'em.campaign_recipient_id', '=', 'ecr.id')
            ->where('ecr.campaign_id', $campaign->getKey())
            ->selectRaw('COUNT(ecr.id) as total')
            ->selectRaw('SUM(CASE WHEN ecr.status = ? THEN 1 ELSE 0 END) as pending', [CampaignRecipientStatus::Pending->value])
            ->selectRaw('SUM(CASE WHEN ecr.status = ? THEN 1 ELSE 0 END) as queued', [CampaignRecipientStatus::Queued->value])
            ->selectRaw('SUM(CASE WHEN ecr.sent_at IS NOT NULL THEN 1 ELSE 0 END) as sent')
            ->selectRaw('SUM(CASE WHEN em.delivered_at IS NOT NULL THEN 1 ELSE 0 END) as delivered')
            ->selectRaw('SUM(CASE WHEN ecr.opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened')
            ->selectRaw('SUM(CASE WHEN ecr.clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked')
            ->selectRaw('SUM(CASE WHEN ecr.status = ? THEN 1 ELSE 0 END) as failed', [CampaignRecipientStatus::Failed->value])
            ->selectRaw('SUM(CASE WHEN ecr.status = ? THEN 1 ELSE 0 END) as bounced', [CampaignRecipientStatus::Bounced->value])
            ->selectRaw('SUM(CASE WHEN ecr.status = ? THEN 1 ELSE 0 END) as complained', [CampaignRecipientStatus::Complained->value])
            ->selectRaw('SUM(CASE WHEN ecr.status = ? THEN 1 ELSE 0 END) as suppressed', [CampaignRecipientStatus::Suppressed->value])
            ->first();

        $eventMetrics = DB::table('email_events as ee')
            ->join('email_messages as em', 'em.id', '=', 'ee.message_id')
            ->join('email_campaign_recipients as ecr', 'ecr.id', '=', 'em.campaign_recipient_id')
            ->where('ecr.campaign_id', $campaign->getKey())
            ->whereIn('ee.event_type', [
                EmailEventType::Opened->value,
                EmailEventType::Clicked->value,
                EmailEventType::Unsubscribed->value,
            ])
            ->selectRaw('SUM(CASE WHEN ee.event_type = ? THEN 1 ELSE 0 END) as total_opens', [EmailEventType::Opened->value])
            ->selectRaw('SUM(CASE WHEN ee.event_type = ? THEN 1 ELSE 0 END) as total_clicks', [EmailEventType::Clicked->value])
            ->selectRaw('COUNT(DISTINCT CASE WHEN ee.event_type = ? THEN ee.message_id END) as unsubscribed', [EmailEventType::Unsubscribed->value])
            ->first();

        $messageFailures = DB::table('email_messages as em')
            ->join('email_campaign_recipients as ecr', 'ecr.id', '=', 'em.campaign_recipient_id')
            ->where('ecr.campaign_id', $campaign->getKey())
            ->selectRaw('SUM(CASE WHEN em.status = ? THEN 1 ELSE 0 END) as failed_messages', [EmailMessageStatus::Failed->value])
            ->first();

        $total = (int) ($metrics->total ?? 0);
        $pending = (int) ($metrics->pending ?? 0);
        $queued = (int) ($metrics->queued ?? 0);
        $sent = (int) ($metrics->sent ?? 0);
        $delivered = (int) ($metrics->delivered ?? 0);
        $opened = (int) ($metrics->opened ?? 0);
        $clicked = (int) ($metrics->clicked ?? 0);
        $failed = max((int) ($metrics->failed ?? 0), (int) ($messageFailures->failed_messages ?? 0));
        $bounced = (int) ($metrics->bounced ?? 0);
        $complained = (int) ($metrics->complained ?? 0);
        $suppressed = (int) ($metrics->suppressed ?? 0);
        $unsubscribed = (int) ($eventMetrics->unsubscribed ?? 0);
        $totalOpens = (int) ($eventMetrics->total_opens ?? 0);
        $totalClicks = (int) ($eventMetrics->total_clicks ?? 0);

        return new CampaignAnalyticsResult(
            total: $total,
            pending: $pending,
            queued: $queued,
            sent: $sent,
            delivered: $delivered,
            opened: $opened,
            totalOpens: $totalOpens,
            clicked: $clicked,
            totalClicks: $totalClicks,
            failed: $failed,
            bounced: $bounced,
            complained: $complained,
            suppressed: $suppressed,
            unsubscribed: $unsubscribed,
            deliveryRate: $capabilities->delivery ? $this->rate($delivered, $sent) : null,
            openRate: $this->rate($opened, $sent),
            clickRate: $this->rate($clicked, $sent),
            clickToOpenRate: $this->rate($clicked, $opened),
            failureRate: $this->rate($failed, $total),
            bounceRate: $capabilities->bounce ? $this->rate($bounced, $sent) : null,
            complaintRate: $capabilities->complaint ? $this->rate($complained, $sent) : null,
            unsubscribeRate: $this->rate($unsubscribed, $sent),
            capabilities: $capabilities,
        );
    }

    private function rate(int $value, int $base): float
    {
        if ($base === 0) {
            return 0.0;
        }

        return round(($value / $base) * 100, 1);
    }
}
