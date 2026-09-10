<?php

namespace Dth\Email\Services;

use Carbon\CarbonImmutable;
use Dth\Email\DTO\AnalyticsMetricDelta;
use Dth\Email\DTO\AnalyticsRange;
use Dth\Email\DTO\EmailAnalyticsFilters;
use Dth\Email\DTO\EmailAnalyticsOverview;
use Dth\Email\DTO\EmailAnalyticsSnapshot;
use Dth\Email\DTO\EmailAnalyticsTrendPoint;
use Dth\Email\DTO\EmailEngagementFunnel;
use Dth\Email\DTO\EmailSystemHealthResult;
use Dth\Email\DTO\SendingAccountPerformanceResult;
use Dth\Email\DTO\TopCampaignResult;
use Dth\Email\DTO\TopLinkResult;
use Dth\Email\DTO\TransportCapabilities;
use Dth\Email\Enums\AnalyticsGranularity;
use Dth\Email\Enums\CampaignRecipientStatus;
use Dth\Email\Enums\EmailEventType;
use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Enums\SendingDomainStatus;
use Dth\Email\Models\EmailCampaign;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class EmailAnalyticsService
{
    private const CACHE_VERSION = 'v1';

    public function __construct(
        private readonly TransportCapabilityService $capabilities,
        private readonly CampaignAnalyticsService $campaignAnalytics,
    ) {}

    public function overview(EmailAnalyticsFilters $filters): EmailAnalyticsOverview
    {
        $this->assertRangeAllowed($filters->range);

        return $this->remember(
            'overview:'.$filters->cacheKey(),
            function () use ($filters): EmailAnalyticsOverview {
                $providers = $this->providersForFilters($filters);
                $capabilities = $this->capabilities->combine($providers);
                $current = $this->snapshot($filters, $capabilities);

                $previous = null;
                $deltas = [];

                if ($filters->comparePrevious && ($previousRange = $filters->range->previous())) {
                    $previousFilters = $filters->forRange($previousRange);
                    $previousCapabilities = $this->capabilities->combine(
                        $this->providersForFilters($previousFilters)
                    );
                    $previous = $this->snapshot($previousFilters, $previousCapabilities);
                    $deltas = $this->deltas($current, $previous);
                }

                return new EmailAnalyticsOverview(
                    range: $filters->range,
                    current: $current,
                    previous: $previous,
                    deltas: $deltas,
                    capabilities: $capabilities,
                );
            },
        );
    }

    /**
     * Activity trend uses the timestamp of the activity itself (sent/open/click/
     * unsubscribe/failure) rather than the campaign-start cohort. This is useful
     * for time-series charts while overview() deliberately uses a send cohort so
     * rates keep a coherent denominator.
     *
     * @return array<int, EmailAnalyticsTrendPoint>
     */
    public function trend(
        EmailAnalyticsFilters $filters,
        AnalyticsGranularity $granularity = AnalyticsGranularity::Day,
    ): array {
        $this->assertRangeAllowed($filters->range);

        if (
            $granularity === AnalyticsGranularity::Hour
            && $filters->range->days() > (int) config('dth-email.analytics.max_hourly_range_days', 14)
        ) {
            throw new InvalidArgumentException('Hourly analytics range is too large.');
        }

        return $this->remember(
            'trend:'.$granularity->value.':'.$filters->cacheKey(),
            fn (): array => $this->buildTrend($filters, $granularity),
        );
    }

    public function engagementFunnel(EmailAnalyticsFilters $filters): EmailEngagementFunnel
    {
        $snapshot = $this->overview($filters)->current;

        return new EmailEngagementFunnel(
            recipients: $snapshot->recipients,
            sent: $snapshot->sent,
            opened: $snapshot->uniqueOpened,
            clicked: $snapshot->uniqueClicked,
            unsubscribed: $snapshot->unsubscribed,
        );
    }

    public function campaignPerformance(int|EmailCampaign $campaign): \Dth\Email\DTO\CampaignAnalyticsResult
    {
        return $campaign instanceof EmailCampaign
            ? $this->campaignAnalytics->forCampaign($campaign)
            : $this->campaignAnalytics->forCampaignId($campaign);
    }

    /** @return array<int, TopCampaignResult> */
    public function topCampaigns(
        EmailAnalyticsFilters $filters,
        int $limit = 10,
    ): array {
        $this->assertRangeAllowed($filters->range);
        $limit = max(1, min($limit, 100));

        return $this->remember(
            "top-campaigns:{$limit}:".$filters->cacheKey(),
            function () use ($filters, $limit): array {
                $sentExpression = 'SUM(CASE WHEN ecr.sent_at IS NOT NULL THEN 1 ELSE 0 END)';
                $clickedExpression = 'SUM(CASE WHEN ecr.clicked_at IS NOT NULL THEN 1 ELSE 0 END)';

                $query = DB::table('email_campaigns as ec')
                    ->leftJoin('email_campaign_recipients as ecr', 'ecr.campaign_id', '=', 'ec.id')
                    ->leftJoin('email_messages as em', 'em.campaign_recipient_id', '=', 'ecr.id')
                    ->whereNull('ec.deleted_at')
                    ->whereBetween('ec.started_at', [$filters->range->start, $filters->range->end]);

                $this->applyCampaignFilters($query, $filters);
                $this->applyMessageStatusFilter($query, $filters);

                $rows = $query
                    ->select([
                        'ec.id',
                        'ec.name',
                        'ec.subject',
                        'ec.status',
                        'ec.started_at',
                    ])
                    ->selectRaw('COUNT(DISTINCT ecr.id) as recipients')
                    ->selectRaw($sentExpression.' as sent')
                    ->selectRaw('SUM(CASE WHEN ecr.opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened')
                    ->selectRaw($clickedExpression.' as clicked')
                    ->selectRaw('SUM(CASE WHEN ecr.status = ? THEN 1 ELSE 0 END) as failed', [CampaignRecipientStatus::Failed->value])
                    ->groupBy('ec.id', 'ec.name', 'ec.subject', 'ec.status', 'ec.started_at')
                    ->orderByRaw("CASE WHEN {$sentExpression} = 0 THEN 0 ELSE (1.0 * {$clickedExpression} / {$sentExpression}) END DESC")
                    ->orderByDesc('sent')
                    ->limit($limit)
                    ->get();

                $campaignIds = $rows->pluck('id')->map(static fn ($id): int => (int) $id)->all();
                $unsubscribeCounts = collect();

                if ($campaignIds !== []) {
                    $unsubscribeQuery = DB::table('email_events as ee')
                        ->join('email_messages as em', 'em.id', '=', 'ee.message_id')
                        ->join('email_campaign_recipients as ecr', 'ecr.id', '=', 'em.campaign_recipient_id')
                        ->where('ee.event_type', EmailEventType::Unsubscribed->value)
                        ->whereIn('ecr.campaign_id', $campaignIds);

                    $this->applyMessageStatusFilter($unsubscribeQuery, $filters);

                    $unsubscribeCounts = $unsubscribeQuery
                        ->select('ecr.campaign_id')
                        ->selectRaw('COUNT(DISTINCT ee.message_id) as unsubscribed')
                        ->groupBy('ecr.campaign_id')
                        ->pluck('unsubscribed', 'ecr.campaign_id');
                }

                return $rows->map(function (object $row) use ($unsubscribeCounts): TopCampaignResult {
                    $sent = (int) $row->sent;
                    $opened = (int) $row->opened;
                    $clicked = (int) $row->clicked;
                    $unsubscribed = (int) ($unsubscribeCounts[(int) $row->id] ?? 0);
                    $failed = (int) $row->failed;

                    return new TopCampaignResult(
                        campaignId: (int) $row->id,
                        name: (string) $row->name,
                        subject: (string) $row->subject,
                        status: (string) $row->status,
                        startedAt: $row->started_at ? CarbonImmutable::parse($row->started_at) : null,
                        recipients: (int) $row->recipients,
                        sent: $sent,
                        opened: $opened,
                        clicked: $clicked,
                        unsubscribed: $unsubscribed,
                        failed: $failed,
                        openRate: $this->rate($opened, $sent),
                        clickRate: $this->rate($clicked, $sent),
                        clickToOpenRate: $this->rate($clicked, $opened),
                        unsubscribeRate: $this->rate($unsubscribed, $sent),
                        failureRate: $this->rate($failed, max((int) $row->recipients, 0)),
                    );
                })->all();
            },
        );
    }

    /** @return array<int, TopLinkResult> */
    public function topLinks(
        EmailAnalyticsFilters $filters,
        int $limit = 10,
    ): array {
        $this->assertRangeAllowed($filters->range);
        $limit = max(1, min($limit, 100));

        return $this->remember(
            "top-links:{$limit}:".$filters->cacheKey(),
            function () use ($filters, $limit): array {
                $base = DB::table('email_tracked_links as etl')
                    ->join('email_messages as em', 'em.id', '=', 'etl.message_id')
                    ->join('email_campaign_recipients as ecr', 'ecr.id', '=', 'em.campaign_recipient_id')
                    ->join('email_campaigns as ec', 'ec.id', '=', 'ecr.campaign_id')
                    ->whereNull('ec.deleted_at')
                    ->whereBetween('ec.started_at', [$filters->range->start, $filters->range->end]);

                $this->applyCampaignFilters($base, $filters);
                $this->applyMessageStatusFilter($base, $filters);

                $totalClicks = (int) (clone $base)->sum('etl.click_count');

                $rows = $base
                    ->select('etl.original_url')
                    ->selectRaw('COUNT(DISTINCT etl.message_id) as messages_containing_link')
                    ->selectRaw('COUNT(DISTINCT CASE WHEN etl.click_count > 0 THEN etl.message_id END) as unique_clickers')
                    ->selectRaw('SUM(etl.click_count) as total_clicks')
                    ->selectRaw('MAX(etl.last_clicked_at) as last_clicked_at')
                    ->groupBy('etl.original_url')
                    ->orderByDesc('total_clicks')
                    ->orderByDesc('unique_clickers')
                    ->limit($limit)
                    ->get();

                return $rows->map(fn (object $row): TopLinkResult => new TopLinkResult(
                    url: (string) $row->original_url,
                    messagesContainingLink: (int) $row->messages_containing_link,
                    uniqueClickers: (int) $row->unique_clickers,
                    totalClicks: (int) $row->total_clicks,
                    lastClickedAt: $row->last_clicked_at ? CarbonImmutable::parse($row->last_clicked_at) : null,
                    clickShare: $this->rate((int) $row->total_clicks, $totalClicks),
                ))->all();
            },
        );
    }

    /** @return array<int, SendingAccountPerformanceResult> */
    public function sendingAccountPerformance(EmailAnalyticsFilters $filters): array
    {
        $this->assertRangeAllowed($filters->range);

        return $this->remember(
            'sending-account-performance:'.$filters->cacheKey(),
            function () use ($filters): array {
                $query = DB::table('email_campaigns as ec')
                    ->join('email_sending_accounts as esa', 'esa.id', '=', 'ec.sending_account_id')
                    ->leftJoin('email_campaign_recipients as ecr', 'ecr.campaign_id', '=', 'ec.id')
                    ->leftJoin('email_messages as em', 'em.campaign_recipient_id', '=', 'ecr.id')
                    ->whereNull('ec.deleted_at')
                    ->whereNull('esa.deleted_at')
                    ->whereBetween('ec.started_at', [$filters->range->start, $filters->range->end]);

                $this->applyCampaignFilters($query, $filters);
                $this->applyMessageStatusFilter($query, $filters);

                $rows = $query
                    ->select([
                        'esa.id',
                        'esa.name',
                        'esa.provider',
                        'esa.status',
                    ])
                    ->selectRaw('COUNT(DISTINCT ec.id) as campaigns')
                    ->selectRaw('COUNT(DISTINCT ecr.id) as recipients')
                    ->selectRaw('SUM(CASE WHEN ecr.sent_at IS NOT NULL THEN 1 ELSE 0 END) as sent')
                    ->selectRaw('SUM(CASE WHEN ecr.opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened')
                    ->selectRaw('SUM(CASE WHEN ecr.clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked')
                    ->selectRaw('SUM(CASE WHEN ecr.status = ? THEN 1 ELSE 0 END) as failed', [CampaignRecipientStatus::Failed->value])
                    ->groupBy('esa.id', 'esa.name', 'esa.provider', 'esa.status')
                    ->orderByDesc('sent')
                    ->get();

                return $rows->map(function (object $row): SendingAccountPerformanceResult {
                    $sent = (int) $row->sent;
                    $opened = (int) $row->opened;
                    $clicked = (int) $row->clicked;
                    $failed = (int) $row->failed;

                    return new SendingAccountPerformanceResult(
                        sendingAccountId: (int) $row->id,
                        name: (string) $row->name,
                        provider: (string) $row->provider,
                        status: (string) $row->status,
                        campaigns: (int) $row->campaigns,
                        recipients: (int) $row->recipients,
                        sent: $sent,
                        opened: $opened,
                        clicked: $clicked,
                        failed: $failed,
                        openRate: $this->rate($opened, $sent),
                        clickRate: $this->rate($clicked, $sent),
                        failureRate: $this->rate($failed, (int) $row->recipients),
                        capabilities: $this->capabilities->forProvider((string) $row->provider),
                    );
                })->all();
            },
        );
    }

    /**
     * Step B exposes an honest operational snapshot. Scheduler/worker heartbeat
     * is intentionally "unknown" until Step I adds persistent heartbeats.
     */
    public function systemHealth(): EmailSystemHealthResult
    {
        return $this->remember(
            'system-health',
            function (): EmailSystemHealthResult {
                $queue = (string) config('dth-email.queue', 'emails');
                $queueConnection = (string) config('queue.default', 'sync');

                $pendingEmailJobs = Schema::hasTable('jobs')
                    ? (int) DB::table('jobs')->where('queue', $queue)->count()
                    : null;

                $failedJobs = Schema::hasTable('failed_jobs')
                    ? (int) DB::table('failed_jobs')->where('queue', $queue)->count()
                    : null;

                return new EmailSystemHealthResult(
                    sendingAccounts: (int) DB::table('email_sending_accounts')->whereNull('deleted_at')->count(),
                    activeSendingAccounts: (int) DB::table('email_sending_accounts')->whereNull('deleted_at')->where('status', SendingAccountStatus::Active->value)->count(),
                    errorSendingAccounts: (int) DB::table('email_sending_accounts')->whereNull('deleted_at')->where('status', SendingAccountStatus::Error->value)->count(),
                    sendingDomains: (int) DB::table('email_sending_domains')->whereNull('deleted_at')->count(),
                    verifiedSendingDomains: (int) DB::table('email_sending_domains')->whereNull('deleted_at')->where('status', SendingDomainStatus::Verified->value)->count(),
                    failedSendingDomains: (int) DB::table('email_sending_domains')->whereNull('deleted_at')->where('status', SendingDomainStatus::Failed->value)->count(),
                    pendingEmailJobs: $pendingEmailJobs,
                    failedJobs: $failedJobs,
                    schedulerHealth: 'unknown',
                    queueWorkerHealth: $queueConnection === 'sync' ? 'not_required' : 'unknown',
                );
            },
            ttlSeconds: min((int) config('dth-email.analytics.cache_ttl_seconds', 120), 30),
        );
    }

    private function snapshot(
        EmailAnalyticsFilters $filters,
        TransportCapabilities $capabilities,
    ): EmailAnalyticsSnapshot {
        $campaigns = (int) $this->campaignCohortQuery($filters)->count('ec.id');

        $recipientMetrics = $this->recipientCohortQuery($filters)
            ->selectRaw('COUNT(ecr.id) as recipients')
            ->selectRaw('SUM(CASE WHEN ecr.status = ? THEN 1 ELSE 0 END) as suppressed', [CampaignRecipientStatus::Suppressed->value])
            ->first();

        $messageMetrics = $this->messageCohortQuery($filters)
            ->selectRaw('COUNT(em.id) as messages')
            ->selectRaw('SUM(CASE WHEN em.sent_at IS NOT NULL THEN 1 ELSE 0 END) as sent')
            ->selectRaw('SUM(CASE WHEN em.delivered_at IS NOT NULL THEN 1 ELSE 0 END) as delivered')
            ->selectRaw('COUNT(DISTINCT CASE WHEN ecr.opened_at IS NOT NULL THEN em.id END) as unique_opened')
            ->selectRaw('COUNT(DISTINCT CASE WHEN ecr.clicked_at IS NOT NULL THEN em.id END) as unique_clicked')
            ->selectRaw('SUM(CASE WHEN em.status = ? THEN 1 ELSE 0 END) as failed', [EmailMessageStatus::Failed->value])
            ->selectRaw('SUM(CASE WHEN em.status = ? THEN 1 ELSE 0 END) as bounced', [EmailMessageStatus::Bounced->value])
            ->selectRaw('SUM(CASE WHEN em.status = ? THEN 1 ELSE 0 END) as complained', [EmailMessageStatus::Complained->value])
            ->first();

        $eventMetrics = $this->eventCohortQuery($filters)
            ->whereIn('ee.event_type', [
                EmailEventType::Opened->value,
                EmailEventType::Clicked->value,
                EmailEventType::Unsubscribed->value,
            ])
            ->selectRaw('SUM(CASE WHEN ee.event_type = ? THEN 1 ELSE 0 END) as total_opens', [EmailEventType::Opened->value])
            ->selectRaw('SUM(CASE WHEN ee.event_type = ? THEN 1 ELSE 0 END) as total_clicks', [EmailEventType::Clicked->value])
            ->selectRaw('COUNT(DISTINCT CASE WHEN ee.event_type = ? THEN ee.message_id END) as unsubscribed', [EmailEventType::Unsubscribed->value])
            ->first();

        $recipients = (int) ($recipientMetrics->recipients ?? 0);
        $messages = (int) ($messageMetrics->messages ?? 0);
        $sent = (int) ($messageMetrics->sent ?? 0);
        $delivered = (int) ($messageMetrics->delivered ?? 0);
        $opened = (int) ($messageMetrics->unique_opened ?? 0);
        $clicked = (int) ($messageMetrics->unique_clicked ?? 0);
        $totalOpens = (int) ($eventMetrics->total_opens ?? 0);
        $totalClicks = (int) ($eventMetrics->total_clicks ?? 0);
        $failed = (int) ($messageMetrics->failed ?? 0);
        $bounced = (int) ($messageMetrics->bounced ?? 0);
        $complained = (int) ($messageMetrics->complained ?? 0);
        $suppressed = (int) ($recipientMetrics->suppressed ?? 0);
        $unsubscribed = (int) ($eventMetrics->unsubscribed ?? 0);

        return new EmailAnalyticsSnapshot(
            campaigns: $campaigns,
            recipients: $recipients,
            messages: $messages,
            sent: $sent,
            delivered: $delivered,
            uniqueOpened: $opened,
            totalOpens: $totalOpens,
            uniqueClicked: $clicked,
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
            failureRate: $this->rate($failed, $messages),
            bounceRate: $capabilities->bounce ? $this->rate($bounced, $sent) : null,
            complaintRate: $capabilities->complaint ? $this->rate($complained, $sent) : null,
            unsubscribeRate: $this->rate($unsubscribed, $sent),
        );
    }

    /** @return array<string, AnalyticsMetricDelta> */
    private function deltas(
        EmailAnalyticsSnapshot $current,
        EmailAnalyticsSnapshot $previous,
    ): array {
        $metrics = [
            'campaigns' => [$current->campaigns, $previous->campaigns],
            'recipients' => [$current->recipients, $previous->recipients],
            'sent' => [$current->sent, $previous->sent],
            'open_rate' => [$current->openRate, $previous->openRate],
            'click_rate' => [$current->clickRate, $previous->clickRate],
            'click_to_open_rate' => [$current->clickToOpenRate, $previous->clickToOpenRate],
            'unsubscribe_rate' => [$current->unsubscribeRate, $previous->unsubscribeRate],
            'failure_rate' => [$current->failureRate, $previous->failureRate],
            'delivery_rate' => [$current->deliveryRate, $previous->deliveryRate],
        ];

        $result = [];

        foreach ($metrics as $metric => [$currentValue, $previousValue]) {
            $result[$metric] = $this->delta($metric, $currentValue, $previousValue);
        }

        return $result;
    }

    private function delta(
        string $metric,
        int|float|null $current,
        int|float|null $previous,
    ): AnalyticsMetricDelta {
        if ($current === null || $previous === null) {
            return new AnalyticsMetricDelta(
                metric: $metric,
                current: $current,
                previous: $previous,
                absoluteChange: null,
                percentChange: null,
                direction: 'unavailable',
            );
        }

        $absolute = round((float) $current - (float) $previous, 2);
        $percent = (float) $previous === 0.0
            ? ((float) $current === 0.0 ? 0.0 : null)
            : round(($absolute / abs((float) $previous)) * 100, 1);

        return new AnalyticsMetricDelta(
            metric: $metric,
            current: $current,
            previous: $previous,
            absoluteChange: $absolute,
            percentChange: $percent,
            direction: $absolute > 0 ? 'up' : ($absolute < 0 ? 'down' : 'flat'),
        );
    }

    /** @return array<int, EmailAnalyticsTrendPoint> */
    private function buildTrend(
        EmailAnalyticsFilters $filters,
        AnalyticsGranularity $granularity,
    ): array {
        $buckets = [];

        for (
            $cursor = $this->bucketStart($filters->range->start, $granularity);
            $cursor->lte($filters->range->end);
            $cursor = $this->nextBucket($cursor, $granularity)
        ) {
            $key = $this->bucketKey($cursor, $granularity);
            $buckets[$key] = [
                'bucket' => $cursor,
                'sent' => 0,
                'unique_opened' => 0,
                'total_opens' => 0,
                'unique_clicked' => 0,
                'total_clicks' => 0,
                'unsubscribed' => 0,
                'failed' => 0,
            ];
        }

        $sentBucket = $this->bucketExpression('em.sent_at', $granularity);
        $sentQuery = $this->messageActivityQuery($filters)
            ->whereBetween('em.sent_at', [$filters->range->start, $filters->range->end])
            ->selectRaw("{$sentBucket} as bucket")
            ->selectRaw('COUNT(em.id) as total')
            ->groupByRaw($sentBucket)
            ->get();

        foreach ($sentQuery as $row) {
            if (isset($buckets[$row->bucket])) {
                $buckets[$row->bucket]['sent'] = (int) $row->total;
            }
        }

        $eventBucket = $this->bucketExpression('ee.occurred_at', $granularity);
        $eventQuery = $this->eventActivityQuery($filters)
            ->whereBetween('ee.occurred_at', [$filters->range->start, $filters->range->end])
            ->whereIn('ee.event_type', [
                EmailEventType::Opened->value,
                EmailEventType::Clicked->value,
                EmailEventType::Unsubscribed->value,
            ])
            ->selectRaw("{$eventBucket} as bucket")
            ->addSelect('ee.event_type')
            ->selectRaw('COUNT(ee.id) as total')
            ->selectRaw('COUNT(DISTINCT ee.message_id) as unique_total')
            ->groupByRaw($eventBucket.', ee.event_type')
            ->get();

        foreach ($eventQuery as $row) {
            if (! isset($buckets[$row->bucket])) {
                continue;
            }

            if ($row->event_type === EmailEventType::Opened->value) {
                $buckets[$row->bucket]['unique_opened'] = (int) $row->unique_total;
                $buckets[$row->bucket]['total_opens'] = (int) $row->total;
            } elseif ($row->event_type === EmailEventType::Clicked->value) {
                $buckets[$row->bucket]['unique_clicked'] = (int) $row->unique_total;
                $buckets[$row->bucket]['total_clicks'] = (int) $row->total;
            } elseif ($row->event_type === EmailEventType::Unsubscribed->value) {
                $buckets[$row->bucket]['unsubscribed'] = (int) $row->unique_total;
            }
        }

        $failedBucket = $this->bucketExpression('em.failed_at', $granularity);
        $failedQuery = $this->messageActivityQuery($filters)
            ->where('em.status', EmailMessageStatus::Failed->value)
            ->whereBetween('em.failed_at', [$filters->range->start, $filters->range->end])
            ->selectRaw("{$failedBucket} as bucket")
            ->selectRaw('COUNT(em.id) as total')
            ->groupByRaw($failedBucket)
            ->get();

        foreach ($failedQuery as $row) {
            if (isset($buckets[$row->bucket])) {
                $buckets[$row->bucket]['failed'] = (int) $row->total;
            }
        }

        return array_map(
            static fn (array $row): EmailAnalyticsTrendPoint => new EmailAnalyticsTrendPoint(
                bucket: $row['bucket'],
                sent: $row['sent'],
                uniqueOpened: $row['unique_opened'],
                totalOpens: $row['total_opens'],
                uniqueClicked: $row['unique_clicked'],
                totalClicks: $row['total_clicks'],
                unsubscribed: $row['unsubscribed'],
                failed: $row['failed'],
            ),
            array_values($buckets),
        );
    }

    /** @return array<int, string> */
    private function providersForFilters(EmailAnalyticsFilters $filters): array
    {
        $providers = $this->messageCohortQuery($filters)
            ->join('email_sending_accounts as esa', 'esa.id', '=', 'em.sending_account_id')
            ->distinct()
            ->pluck('esa.provider')
            ->filter()
            ->map(static fn ($provider): string => (string) $provider)
            ->values()
            ->all();

        if ($providers !== []) {
            return $providers;
        }

        if ($filters->sendingAccountId !== null) {
            $provider = DB::table('email_sending_accounts')
                ->where('id', $filters->sendingAccountId)
                ->value('provider');

            return $provider ? [(string) $provider] : ['smtp'];
        }

        if ($filters->campaignId !== null) {
            $provider = DB::table('email_campaigns as ec')
                ->join('email_sending_accounts as esa', 'esa.id', '=', 'ec.sending_account_id')
                ->where('ec.id', $filters->campaignId)
                ->value('esa.provider');

            return $provider ? [(string) $provider] : ['smtp'];
        }

        $providers = DB::table('email_sending_accounts')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('provider')
            ->filter()
            ->map(static fn ($provider): string => (string) $provider)
            ->values()
            ->all();

        return $providers !== [] ? $providers : ['smtp'];
    }

    private function campaignCohortQuery(EmailAnalyticsFilters $filters): Builder
    {
        $query = DB::table('email_campaigns as ec')
            ->whereNull('ec.deleted_at')
            ->whereBetween('ec.started_at', [$filters->range->start, $filters->range->end]);

        $this->applyCampaignFilters($query, $filters);

        if (($messageStatus = $filters->messageStatusValue()) !== null && $messageStatus !== '') {
            $query->whereExists(function (Builder $subQuery) use ($messageStatus): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('email_campaign_recipients as filter_ecr')
                    ->join('email_messages as filter_em', 'filter_em.campaign_recipient_id', '=', 'filter_ecr.id')
                    ->whereColumn('filter_ecr.campaign_id', 'ec.id')
                    ->where('filter_em.status', $messageStatus);
            });
        }

        return $query;
    }

    private function recipientCohortQuery(EmailAnalyticsFilters $filters): Builder
    {
        $query = DB::table('email_campaign_recipients as ecr')
            ->join('email_campaigns as ec', 'ec.id', '=', 'ecr.campaign_id')
            ->whereNull('ec.deleted_at')
            ->whereBetween('ec.started_at', [$filters->range->start, $filters->range->end]);

        $this->applyCampaignFilters($query, $filters);

        if (($messageStatus = $filters->messageStatusValue()) !== null && $messageStatus !== '') {
            $query->whereExists(function (Builder $subQuery) use ($messageStatus): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('email_messages as filter_em')
                    ->whereColumn('filter_em.campaign_recipient_id', 'ecr.id')
                    ->where('filter_em.status', $messageStatus);
            });
        }

        return $query;
    }

    private function messageCohortQuery(EmailAnalyticsFilters $filters): Builder
    {
        $query = DB::table('email_messages as em')
            ->join('email_campaign_recipients as ecr', 'ecr.id', '=', 'em.campaign_recipient_id')
            ->join('email_campaigns as ec', 'ec.id', '=', 'ecr.campaign_id')
            ->whereNull('ec.deleted_at')
            ->whereBetween('ec.started_at', [$filters->range->start, $filters->range->end]);

        $this->applyCampaignFilters($query, $filters);
        $this->applyMessageStatusFilter($query, $filters);

        return $query;
    }

    private function eventCohortQuery(EmailAnalyticsFilters $filters): Builder
    {
        $query = DB::table('email_events as ee')
            ->join('email_messages as em', 'em.id', '=', 'ee.message_id')
            ->join('email_campaign_recipients as ecr', 'ecr.id', '=', 'em.campaign_recipient_id')
            ->join('email_campaigns as ec', 'ec.id', '=', 'ecr.campaign_id')
            ->whereNull('ec.deleted_at')
            ->whereBetween('ec.started_at', [$filters->range->start, $filters->range->end]);

        $this->applyCampaignFilters($query, $filters);
        $this->applyMessageStatusFilter($query, $filters);

        return $query;
    }

    private function messageActivityQuery(EmailAnalyticsFilters $filters): Builder
    {
        $query = DB::table('email_messages as em')
            ->join('email_campaign_recipients as ecr', 'ecr.id', '=', 'em.campaign_recipient_id')
            ->join('email_campaigns as ec', 'ec.id', '=', 'ecr.campaign_id')
            ->whereNull('ec.deleted_at');

        $this->applyCampaignFilters($query, $filters);
        $this->applyMessageStatusFilter($query, $filters);

        return $query;
    }

    private function eventActivityQuery(EmailAnalyticsFilters $filters): Builder
    {
        $query = DB::table('email_events as ee')
            ->join('email_messages as em', 'em.id', '=', 'ee.message_id')
            ->join('email_campaign_recipients as ecr', 'ecr.id', '=', 'em.campaign_recipient_id')
            ->join('email_campaigns as ec', 'ec.id', '=', 'ecr.campaign_id')
            ->whereNull('ec.deleted_at');

        $this->applyCampaignFilters($query, $filters);
        $this->applyMessageStatusFilter($query, $filters);

        return $query;
    }

    private function applyCampaignFilters(Builder $query, EmailAnalyticsFilters $filters): void
    {
        if ($filters->sendingAccountId !== null) {
            $query->where('ec.sending_account_id', $filters->sendingAccountId);
        }

        if ($filters->campaignId !== null) {
            $query->where('ec.id', $filters->campaignId);
        }

        if (($status = $filters->campaignStatusValue()) !== null && $status !== '') {
            $query->where('ec.status', $status);
        }
    }

    private function applyMessageStatusFilter(Builder $query, EmailAnalyticsFilters $filters): void
    {
        if (($status = $filters->messageStatusValue()) !== null && $status !== '') {
            $query->where('em.status', $status);
        }
    }

    private function bucketExpression(
        string $qualifiedColumn,
        AnalyticsGranularity $granularity,
    ): string {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => $granularity === AnalyticsGranularity::Hour
                ? "strftime('%Y-%m-%d %H:00:00', {$qualifiedColumn})"
                : "strftime('%Y-%m-%d', {$qualifiedColumn})",
            'pgsql' => $granularity === AnalyticsGranularity::Hour
                ? "to_char(date_trunc('hour', {$qualifiedColumn}), 'YYYY-MM-DD HH24:00:00')"
                : "to_char({$qualifiedColumn}, 'YYYY-MM-DD')",
            default => $granularity === AnalyticsGranularity::Hour
                ? "DATE_FORMAT({$qualifiedColumn}, '%Y-%m-%d %H:00:00')"
                : "DATE_FORMAT({$qualifiedColumn}, '%Y-%m-%d')",
        };
    }

    private function bucketStart(
        CarbonImmutable $date,
        AnalyticsGranularity $granularity,
    ): CarbonImmutable {
        return $granularity === AnalyticsGranularity::Hour
            ? $date->startOfHour()
            : $date->startOfDay();
    }

    private function nextBucket(
        CarbonImmutable $date,
        AnalyticsGranularity $granularity,
    ): CarbonImmutable {
        return $granularity === AnalyticsGranularity::Hour
            ? $date->addHour()
            : $date->addDay();
    }

    private function bucketKey(
        CarbonImmutable $date,
        AnalyticsGranularity $granularity,
    ): string {
        return $granularity === AnalyticsGranularity::Hour
            ? $date->format('Y-m-d H:00:00')
            : $date->format('Y-m-d');
    }

    private function rate(int|float $value, int|float $base): float
    {
        if ((float) $base <= 0.0) {
            return 0.0;
        }

        return round(((float) $value / (float) $base) * 100, 1);
    }

    private function assertRangeAllowed(AnalyticsRange $range): void
    {
        $maxDays = max(1, (int) config('dth-email.analytics.max_range_days', 366));

        if ($range->days() > $maxDays) {
            throw new InvalidArgumentException("Analytics range cannot exceed {$maxDays} days.");
        }
    }

    private function remember(
        string $key,
        callable $callback,
        ?int $ttlSeconds = null,
    ): mixed {
        $ttlSeconds ??= (int) config('dth-email.analytics.cache_ttl_seconds', 120);

        if ($ttlSeconds <= 0) {
            return $callback();
        }

        return Cache::remember(
            'dth-email:analytics:'.self::CACHE_VERSION.':'.$key,
            now()->addSeconds($ttlSeconds),
            $callback,
        );
    }
}
