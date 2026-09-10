<?php

namespace Dth\Email\Services;

use Dth\Email\DTO\CampaignAnalyticsResult;
use Dth\Email\Enums\CampaignRecipientStatus;
use Dth\Email\Models\EmailCampaign;
use Illuminate\Database\Eloquent\Builder;

class CampaignAnalyticsService
{
    public function forCampaignId(
        int $campaignId,
    ): CampaignAnalyticsResult {
        return $this->forCampaign(
            EmailCampaign::query()->findOrFail($campaignId)
        );
    }

    public function forCampaign(
        EmailCampaign $campaign,
    ): CampaignAnalyticsResult {
        $recipients = $campaign->recipients();

        $total = (clone $recipients)->count();

        $pending = (clone $recipients)
            ->where(
                'status',
                CampaignRecipientStatus::Pending->value
            )
            ->count();

        $queued = (clone $recipients)
            ->where(
                'status',
                CampaignRecipientStatus::Queued->value
            )
            ->count();

        $sent = (clone $recipients)
            ->whereNotNull('sent_at')
            ->count();

        $delivered = (clone $recipients)
            ->whereHas(
                'message',
                fn (Builder $query) =>
                    $query->whereNotNull('delivered_at')
            )
            ->count();

        $opened = (clone $recipients)
            ->whereNotNull('opened_at')
            ->count();

        $clicked = (clone $recipients)
            ->whereNotNull('clicked_at')
            ->count();

        $failed = (clone $recipients)
            ->where(
                'status',
                CampaignRecipientStatus::Failed->value
            )
            ->count();

        $bounced = (clone $recipients)
            ->where(
                'status',
                CampaignRecipientStatus::Bounced->value
            )
            ->count();

        $complained = (clone $recipients)
            ->where(
                'status',
                CampaignRecipientStatus::Complained->value
            )
            ->count();

        $suppressed = (clone $recipients)
            ->where(
                'status',
                CampaignRecipientStatus::Suppressed->value
            )
            ->count();

        $unsubscribed = (clone $recipients)
            ->where(
                'status',
                CampaignRecipientStatus::Unsubscribed->value
            )
            ->count();

        return new CampaignAnalyticsResult(
            total: $total,
            pending: $pending,
            queued: $queued,
            sent: $sent,
            delivered: $delivered,
            opened: $opened,
            clicked: $clicked,
            failed: $failed,
            bounced: $bounced,
            complained: $complained,
            suppressed: $suppressed,
            unsubscribed: $unsubscribed,

            deliveryRate: $this->rate(
                $delivered,
                $sent,
            ),

            openRate: $this->rate(
                $opened,
                $sent,
            ),

            clickRate: $this->rate(
                $clicked,
                $sent,
            ),

            failureRate: $this->rate(
                $failed + $bounced,
                $total,
            ),

            unsubscribeRate: $this->rate(
                $unsubscribed,
                $sent,
            ),
        );
    }

    private function rate(
        int $value,
        int $base,
    ): float {
        if ($base === 0) {
            return 0;
        }

        return round(
            ($value / $base) * 100,
            1,
        );
    }
}