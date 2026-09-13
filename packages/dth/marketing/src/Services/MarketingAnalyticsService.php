<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Contracts\EmailMarketingBridge;
use Dth\Marketing\Contracts\LeadProvider;
use Dth\Marketing\Contracts\RevenueProvider;
use Dth\Marketing\DTO\MarketingAnalyticsFilter;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\LandingPageSubmission;
use Dth\Marketing\Models\LandingPageView;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Models\MarketingCampaignEmailLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

final class MarketingAnalyticsService
{
    public function __construct(
        private readonly RevenueProvider $revenueProvider,
        private readonly LeadProvider $leadProvider,
        private readonly EmailMarketingBridge $emailBridge,
        private readonly MarketingInsightService $insights,
    ) {}

    /** @return array<string, mixed> */
    public function dashboard(MarketingAnalyticsFilter $filter): array
    {
        $summary = $this->summary($filter);
        $campaigns = $this->campaignPerformance($filter, 12);
        $landingPages = $this->landingPagePerformance($filter, 12);
        $sources = $this->sourcePerformance($filter, 12);
        $utmMediums = $this->utmDimensionPerformance($filter, 'utm_medium', 12);
        $utmCampaigns = $this->utmDimensionPerformance($filter, 'utm_campaign', 12);
        $trend = $this->trend($filter);
        $emailCampaigns = $this->emailPerformance($filter, 12);

        $payload = [
            'filter' => $filter,
            'summary' => $summary,
            'funnel' => [
                ['label' => 'Views', 'value' => $summary['views'], 'available' => true],
                ['label' => 'Submissions', 'value' => $summary['submissions'], 'available' => true],
                ['label' => 'Leads', 'value' => $summary['leads'], 'available' => $summary['leads_available']],
                ['label' => 'Customers', 'value' => $summary['customers'], 'available' => $summary['financial_available']],
            ],
            'trend' => $trend,
            'sources' => $sources,
            'utm_mediums' => $utmMediums,
            'utm_campaigns' => $utmCampaigns,
            'campaigns' => $campaigns,
            'landing_pages' => $landingPages,
            'email_campaigns' => $emailCampaigns,
        ];

        $payload['insights'] = $this->insights->forDashboard($payload);

        return $payload;
    }

    /** @return array<string, mixed> */
    public function campaignReport(MarketingCampaign $campaign, MarketingAnalyticsFilter $filter): array
    {
        $filter = new MarketingAnalyticsFilter(
            start: $filter->start,
            end: $filter->end,
            previousStart: $filter->previousStart,
            previousEnd: $filter->previousEnd,
            period: $filter->period,
            campaignId: (int) $campaign->getKey(),
            campaignStatus: null,
            source: $filter->source,
        );

        $dashboard = $this->dashboard($filter);
        $financial = $this->financialForCampaign($campaign, $filter);

        return [
            ...$dashboard,
            'campaign' => $campaign,
            'financial' => $financial,
            'email_campaigns' => $this->emailPerformance($filter, 100),
            'utm_links' => $campaign->landingPages()
                ->with('utmUrls')
                ->get()
                ->flatMap(fn (LandingPage $page): Collection => $page->utmUrls->map(fn ($url): array => [
                    'landing_page' => $page->name,
                    'name' => $url->name,
                    'source' => $url->utm_source,
                    'medium' => $url->utm_medium,
                    'campaign' => $url->utm_campaign,
                    'url' => $url->url,
                ]))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function summary(MarketingAnalyticsFilter $filter): array
    {
        $views = $this->viewQuery($filter)->count();
        $submissions = $this->submissionQuery($filter)->count();
        $failed = (clone $this->submissionQuery($filter))->where('status', 'failed')->count();
        $spam = (clone $this->submissionQuery($filter))->where('status', 'spam')->count();

        $leadsAvailable = $this->leadProvider->available();
        $leads = $leadsAvailable
            ? (clone $this->submissionQuery($filter))->whereNotNull('lead_reference')->count()
            : null;

        $campaigns = $this->campaignQuery($filter)->get();
        $budget = (float) $campaigns->sum(fn (MarketingCampaign $campaign): float => (float) ($campaign->budget ?? 0));

        $financial = $this->financialForCampaigns($campaigns, $filter);
        $revenue = $financial['available'] ? $financial['revenue'] : null;
        $customers = $financial['available'] ? $financial['customers'] : null;

        $previousViews = $this->viewQuery($filter, true)->count();
        $previousSubmissions = $this->submissionQuery($filter, true)->count();
        $previousLeads = $leadsAvailable
            ? (clone $this->submissionQuery($filter, true))->whereNotNull('lead_reference')->count()
            : null;

        return [
            'views' => $views,
            'submissions' => $submissions,
            'leads' => $leads,
            'customers' => $customers,
            'budget' => $budget,
            'revenue' => $revenue,
            'currency' => $financial['currency'],
            'roas' => $financial['available'] && $budget > 0 ? round(((float) $revenue) / $budget, 2) : null,
            'view_to_submission' => $views > 0 ? round(($submissions / $views) * 100, 2) : 0.0,
            'submission_to_lead' => $leadsAvailable && $submissions > 0 ? round(((int) $leads / $submissions) * 100, 2) : null,
            'lead_to_customer' => $leadsAvailable && $financial['available'] && (int) $leads > 0
                ? round(((int) $customers / (int) $leads) * 100, 2)
                : null,
            'failed' => $failed,
            'spam' => $spam,
            'failure_rate' => $submissions > 0 ? round(($failed / $submissions) * 100, 2) : 0.0,
            'spam_rate' => $submissions > 0 ? round(($spam / $submissions) * 100, 2) : 0.0,
            'leads_available' => $leadsAvailable,
            'financial_available' => $financial['available'],
            'financial_partial' => $financial['partial'],
            'financial_reason' => $financial['reason'],
            'deltas' => [
                'views' => $this->delta($views, $previousViews),
                'submissions' => $this->delta($submissions, $previousSubmissions),
                'leads' => $leadsAvailable ? $this->delta((int) $leads, (int) $previousLeads) : null,
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function campaignPerformance(MarketingAnalyticsFilter $filter, int $limit = 12): array
    {
        $campaigns = $this->campaignQuery($filter)
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->limit(max($limit * 2, 24))
            ->get();

        return $campaigns
            ->map(function (MarketingCampaign $campaign) use ($filter): array {
                $rowFilter = $this->filterForCampaign($filter, (int) $campaign->getKey());
                $views = $this->viewQuery($rowFilter)->count();
                $submissions = $this->submissionQuery($rowFilter)->count();
                $leads = $this->leadProvider->available()
                    ? (clone $this->submissionQuery($rowFilter))->whereNotNull('lead_reference')->count()
                    : null;
                $financial = $this->financialForCampaign($campaign, $rowFilter);
                $budget = (float) ($campaign->budget ?? 0);

                return [
                    'id' => (int) $campaign->getKey(),
                    'name' => (string) $campaign->name,
                    'status' => $campaign->status instanceof \BackedEnum ? $campaign->status->value : (string) $campaign->status,
                    'budget' => $budget,
                    'currency' => (string) ($campaign->currency ?: 'VND'),
                    'views' => $views,
                    'submissions' => $submissions,
                    'leads' => $leads,
                    'customers' => $financial['available'] ? $financial['customers'] : null,
                    'revenue' => $financial['available'] ? $financial['revenue'] : null,
                    'roas' => $financial['available'] && $budget > 0
                        ? round(((float) $financial['revenue']) / $budget, 2)
                        : null,
                    'view_to_submission' => $views > 0 ? round(($submissions / $views) * 100, 2) : 0.0,
                    'financial_available' => $financial['available'],
                ];
            })
            ->filter(fn (array $row): bool => $row['views'] > 0
                || $row['submissions'] > 0
                || $row['status'] === 'active'
                || (float) ($row['revenue'] ?? 0) > 0)
            ->sortByDesc(fn (array $row): float => (float) ($row['revenue'] ?? 0) + ($row['submissions'] * 0.001))
            ->take($limit)
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function landingPagePerformance(MarketingAnalyticsFilter $filter, int $limit = 12): array
    {
        $query = LandingPage::query();
        $this->applyCampaignFiltersToLandingQuery($query, $filter);

        return $query
            ->orderBy('name')
            ->get()
            ->map(function (LandingPage $page) use ($filter): array {
                $viewsQuery = LandingPageView::query()
                    ->where('landing_page_id', $page->getKey())
                    ->whereBetween('viewed_at', [$filter->start, $filter->end]);
                $submissionsQuery = LandingPageSubmission::query()
                    ->where('landing_page_id', $page->getKey())
                    ->whereBetween('submitted_at', [$filter->start, $filter->end]);

                $this->applySourceFilter($viewsQuery, 'view', $filter->source);
                $this->applySourceFilter($submissionsQuery, 'submission', $filter->source);

                $views = $viewsQuery->count();
                $submissions = $submissionsQuery->count();
                $leads = $this->leadProvider->available()
                    ? (clone $submissionsQuery)->whereNotNull('lead_reference')->count()
                    : null;

                return [
                    'id' => (int) $page->getKey(),
                    'name' => (string) $page->name,
                    'slug' => (string) $page->slug,
                    'status' => $page->status instanceof \BackedEnum ? $page->status->value : (string) $page->status,
                    'views' => $views,
                    'submissions' => $submissions,
                    'leads' => $leads,
                    'conversion_rate' => $views > 0 ? round(($submissions / $views) * 100, 2) : 0.0,
                ];
            })
            ->filter(fn (array $row): bool => $row['views'] > 0 || $row['submissions'] > 0 || $row['status'] === 'published')
            ->sortByDesc('submissions')
            ->take($limit)
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function sourcePerformance(MarketingAnalyticsFilter $filter, int $limit = 12): array
    {
        $views = $this->viewQuery($this->withoutSource($filter))
            ->selectRaw("COALESCE(NULLIF(source, ''), NULLIF(utm_source, ''), 'direct') as attribution_source, COUNT(*) as views_count")
            ->groupBy('attribution_source')
            ->pluck('views_count', 'attribution_source');

        $submissions = $this->submissionQuery($this->withoutSource($filter))
            ->selectRaw("COALESCE(NULLIF(utm_source, ''), NULLIF(source, ''), 'direct') as attribution_source, COUNT(*) as submissions_count")
            ->groupBy('attribution_source')
            ->get()
            ->keyBy('attribution_source');

        $leadStats = $this->leadProvider->available()
            ? $this->submissionQuery($this->withoutSource($filter))
                ->whereNotNull('lead_reference')
                ->selectRaw("COALESCE(NULLIF(utm_source, ''), NULLIF(source, ''), 'direct') as attribution_source, COUNT(*) as leads_count")
                ->groupBy('attribution_source')
                ->get()
                ->keyBy('attribution_source')
            : collect();

        $sources = collect(array_unique([
            ...$views->keys()->map('strval')->all(),
            ...$submissions->keys()->map('strval')->all(),
        ]));

        return $sources
            ->map(function (string $source) use ($views, $submissions, $leadStats): array {
                $viewCount = (int) ($views->get($source) ?? 0);
                $submissionCount = (int) ($submissions->get($source)?->submissions_count ?? 0);
                $leadCount = $this->leadProvider->available()
                    ? (int) ($leadStats->get($source)?->leads_count ?? 0)
                    : null;

                return [
                    'source' => $source,
                    'views' => $viewCount,
                    'submissions' => $submissionCount,
                    'leads' => $leadCount,
                    'conversion_rate' => $viewCount > 0 ? round(($submissionCount / $viewCount) * 100, 2) : null,
                ];
            })
            ->when($filter->source !== null, fn (Collection $rows): Collection => $rows->where('source', $filter->source))
            ->sortByDesc('submissions')
            ->take($limit)
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function utmDimensionPerformance(
        MarketingAnalyticsFilter $filter,
        string $dimension,
        int $limit = 12,
    ): array {
        if (! in_array($dimension, ['utm_medium', 'utm_campaign'], true)) {
            throw new \InvalidArgumentException('Unsupported UTM attribution dimension.');
        }

        $label = $dimension === 'utm_medium' ? 'medium' : 'campaign';
        $views = $this->viewQuery($filter)
            ->selectRaw("COALESCE(NULLIF({$dimension}, ''), '(none)') as attribution_value, COUNT(*) as views_count")
            ->groupBy('attribution_value')
            ->get()
            ->keyBy('attribution_value');
        $submissions = $this->submissionQuery($filter)
            ->selectRaw("COALESCE(NULLIF({$dimension}, ''), '(none)') as attribution_value, COUNT(*) as submissions_count")
            ->groupBy('attribution_value')
            ->get()
            ->keyBy('attribution_value');
        $leadStats = $this->leadProvider->available()
            ? $this->submissionQuery($filter)
                ->whereNotNull('lead_reference')
                ->selectRaw("COALESCE(NULLIF({$dimension}, ''), '(none)') as attribution_value, COUNT(*) as leads_count")
                ->groupBy('attribution_value')
                ->get()
                ->keyBy('attribution_value')
            : collect();

        $values = collect(array_unique([
            ...$views->keys()->map('strval')->all(),
            ...$submissions->keys()->map('strval')->all(),
        ]));

        return $values
            ->map(function (string $value) use ($views, $submissions, $leadStats, $label): array {
                $viewCount = (int) ($views->get($value)?->views_count ?? 0);
                $submissionCount = (int) ($submissions->get($value)?->submissions_count ?? 0);
                $leadCount = $this->leadProvider->available()
                    ? (int) ($leadStats->get($value)?->leads_count ?? 0)
                    : null;

                return [
                    $label => $value,
                    'views' => $viewCount,
                    'submissions' => $submissionCount,
                    'leads' => $leadCount,
                    'conversion_rate' => $viewCount > 0
                        ? round(($submissionCount / $viewCount) * 100, 2)
                        : null,
                ];
            })
            ->sortByDesc('submissions')
            ->take($limit)
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function emailPerformance(MarketingAnalyticsFilter $filter, int $limit = 12): array
    {
        $query = MarketingCampaignEmailLink::query()->with('marketingCampaign');

        if ($filter->campaignId !== null) {
            $query->where('marketing_campaign_id', $filter->campaignId);
        }
        if ($filter->campaignStatus !== null) {
            $query->whereHas('marketingCampaign', fn (Builder $builder): Builder => $builder->where('status', $filter->campaignStatus));
        }

        return $query
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(function (MarketingCampaignEmailLink $link): array {
                $metrics = (array) ($link->metrics_snapshot ?? []);

                return [
                    'reference' => (string) $link->email_campaign_reference,
                    'name' => (string) ($link->display_name ?: $link->email_campaign_reference),
                    'status' => $link->status_snapshot,
                    'admin_url' => $link->admin_url_snapshot,
                    'recipients' => $this->metricInt($metrics, ['recipients', 'recipients_count', 'total']),
                    'sent' => $this->metricInt($metrics, ['sent', 'sent_count']),
                    'opened' => $this->metricInt($metrics, ['opened', 'opened_count']),
                    'clicked' => $this->metricInt($metrics, ['clicked', 'clicked_count']),
                    'open_rate' => $this->metricFloat($metrics, ['open_rate']),
                    'click_rate' => $this->metricFloat($metrics, ['click_rate']),
                    'metrics_available' => $metrics !== [],
                    'bridge_available' => $this->emailBridge->available(),
                ];
            })
            ->all();
    }

    /** @return array<int, array{date:string,views:int,submissions:int,leads:int|null}> */
    public function trend(MarketingAnalyticsFilter $filter): array
    {
        $views = $this->viewQuery($filter)
            ->selectRaw('DATE(viewed_at) as metric_date, COUNT(*) as metric_count')
            ->groupBy('metric_date')
            ->pluck('metric_count', 'metric_date');
        $submissions = $this->submissionQuery($filter)
            ->selectRaw('DATE(submitted_at) as metric_date, COUNT(*) as metric_count')
            ->groupBy('metric_date')
            ->pluck('metric_count', 'metric_date');
        $leads = $this->leadProvider->available()
            ? $this->submissionQuery($filter)
                ->whereNotNull('lead_reference')
                ->selectRaw('DATE(submitted_at) as metric_date, COUNT(*) as metric_count')
                ->groupBy('metric_date')
                ->pluck('metric_count', 'metric_date')
            : collect();

        $rows = [];
        for ($date = $filter->start->startOfDay(); $date->lte($filter->end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $rows[] = [
                'date' => $key,
                'views' => (int) ($views->get($key) ?? 0),
                'submissions' => (int) ($submissions->get($key) ?? 0),
                'leads' => $this->leadProvider->available() ? (int) ($leads->get($key) ?? 0) : null,
            ];
        }

        return $rows;
    }

    /** @return array<int, string> */
    public function sourceOptions(MarketingAnalyticsFilter $filter): array
    {
        return collect($this->sourcePerformance($this->withoutSource($filter), 100))
            ->pluck('source')
            ->filter()
            ->values()
            ->all();
    }

    /** @return array{available:bool,partial:bool,revenue:float|null,customers:int|null,currency:string,reason:?string} */
    private function financialForCampaigns(Collection $campaigns, MarketingAnalyticsFilter $filter): array
    {
        if (! $this->revenueProvider->available()) {
            return [
                'available' => false,
                'partial' => false,
                'revenue' => null,
                'customers' => null,
                'currency' => 'VND',
                'reason' => 'RevenueProvider is not available.',
            ];
        }

        if ($filter->source !== null) {
            return [
                'available' => false,
                'partial' => false,
                'revenue' => null,
                'customers' => null,
                'currency' => 'VND',
                'reason' => 'RevenueProvider does not expose source-level attribution; financial KPIs are N/A while a source filter is active.',
            ];
        }

        if ($campaigns->isEmpty()) {
            return [
                'available' => true,
                'partial' => false,
                'revenue' => 0.0,
                'customers' => 0,
                'currency' => 'VND',
                'reason' => null,
            ];
        }

        $revenue = 0.0;
        $customers = 0;
        $currency = null;
        $partial = false;

        foreach ($campaigns as $campaign) {
            if (! $campaign instanceof MarketingCampaign) {
                continue;
            }

            $row = $this->financialForCampaign($campaign, $filter);
            if (! $row['available']) {
                $partial = true;
                continue;
            }

            $revenue += (float) $row['revenue'];
            $customers += (int) $row['customers'];
            $currency ??= $row['currency'];

            if ($currency !== $row['currency']) {
                return [
                    'available' => false,
                    'partial' => true,
                    'revenue' => null,
                    'customers' => null,
                    'currency' => $currency ?: 'VND',
                    'reason' => 'Filtered campaigns use different revenue currencies.',
                ];
            }
        }

        if ($partial) {
            return [
                'available' => false,
                'partial' => true,
                'revenue' => null,
                'customers' => null,
                'currency' => $currency ?: 'VND',
                'reason' => 'RevenueProvider returned incomplete data for one or more campaigns.',
            ];
        }

        return [
            'available' => true,
            'partial' => false,
            'revenue' => $revenue,
            'customers' => $customers,
            'currency' => $currency ?: 'VND',
            'reason' => null,
        ];
    }

    /** @return array{available:bool,revenue:float|null,customers:int|null,currency:string,reason:?string} */
    private function financialForCampaign(MarketingCampaign $campaign, MarketingAnalyticsFilter $filter): array
    {
        if (! $this->revenueProvider->available()) {
            return [
                'available' => false,
                'revenue' => null,
                'customers' => null,
                'currency' => (string) ($campaign->currency ?: 'VND'),
                'reason' => 'RevenueProvider is not available.',
            ];
        }

        if ($filter->source !== null) {
            return [
                'available' => false,
                'revenue' => null,
                'customers' => null,
                'currency' => (string) ($campaign->currency ?: 'VND'),
                'reason' => 'RevenueProvider does not expose source-level attribution.',
            ];
        }

        try {
            $summary = $this->revenueProvider->summarizeCampaign(
                (string) $campaign->getKey(),
                $filter->start,
                $filter->end,
            );
        } catch (Throwable $exception) {
            report($exception);
            $summary = null;
        }

        if ($summary === null || $summary->paidCustomers === null) {
            return [
                'available' => false,
                'revenue' => null,
                'customers' => null,
                'currency' => (string) ($campaign->currency ?: 'VND'),
                'reason' => 'RevenueProvider did not return a complete campaign summary.',
            ];
        }

        return [
            'available' => true,
            'revenue' => $this->numericAmount($summary->amount),
            'customers' => $summary->paidCustomers,
            'currency' => strtoupper($summary->currency ?: (string) ($campaign->currency ?: 'VND')),
            'reason' => null,
        ];
    }

    /** @return Builder<MarketingCampaign> */
    private function campaignQuery(MarketingAnalyticsFilter $filter): Builder
    {
        return MarketingCampaign::query()
            ->when($filter->campaignId !== null, fn (Builder $query): Builder => $query->whereKey($filter->campaignId))
            ->when($filter->campaignStatus !== null, fn (Builder $query): Builder => $query->where('status', $filter->campaignStatus))
            ->where(function (Builder $query) use ($filter): void {
                $query->whereNull('start_date')->orWhereDate('start_date', '<=', $filter->end->toDateString());
            })
            ->where(function (Builder $query) use ($filter): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $filter->start->toDateString());
            });
    }

    /** @return Builder<LandingPageView> */
    private function viewQuery(MarketingAnalyticsFilter $filter, bool $previous = false): Builder
    {
        $range = $previous
            ? [$filter->previousStart, $filter->previousEnd]
            : [$filter->start, $filter->end];

        $query = LandingPageView::query()->whereBetween('viewed_at', $range);
        $this->applyCampaignFilters($query, $filter);
        $this->applySourceFilter($query, 'view', $filter->source);

        return $query;
    }

    /** @return Builder<LandingPageSubmission> */
    private function submissionQuery(MarketingAnalyticsFilter $filter, bool $previous = false): Builder
    {
        $range = $previous
            ? [$filter->previousStart, $filter->previousEnd]
            : [$filter->start, $filter->end];

        $query = LandingPageSubmission::query()->whereBetween('submitted_at', $range);
        $this->applyCampaignFilters($query, $filter);
        $this->applySourceFilter($query, 'submission', $filter->source);

        return $query;
    }

    private function applyCampaignFilters(Builder $query, MarketingAnalyticsFilter $filter): void
    {
        if ($filter->campaignId !== null) {
            $query->where('marketing_campaign_id', $filter->campaignId);
        }
        if ($filter->campaignStatus !== null) {
            $query->whereHas('marketingCampaign', fn (Builder $builder): Builder => $builder->where('status', $filter->campaignStatus));
        }
    }

    private function applyCampaignFiltersToLandingQuery(Builder $query, MarketingAnalyticsFilter $filter): void
    {
        if ($filter->campaignId !== null) {
            $query->where('marketing_campaign_id', $filter->campaignId);
        }
        if ($filter->campaignStatus !== null) {
            $query->whereHas('marketingCampaign', fn (Builder $builder): Builder => $builder->where('status', $filter->campaignStatus));
        }
    }

    private function applySourceFilter(Builder $query, string $kind, ?string $source): void
    {
        if ($source === null) {
            return;
        }

        if ($source === 'direct') {
            if ($kind === 'view') {
                $query->where(function (Builder $builder): void {
                    $builder->whereNull('source')->orWhere('source', '')->orWhere('source', 'direct');
                });
            } else {
                $query->where(function (Builder $builder): void {
                    $builder->where(function (Builder $direct): void {
                        $direct->whereNull('utm_source')->orWhere('utm_source', '');
                    })->where(function (Builder $direct): void {
                        $direct->whereNull('source')->orWhere('source', '')->orWhere('source', 'direct');
                    });
                });
            }

            return;
        }

        if ($kind === 'view') {
            $query->where(function (Builder $builder) use ($source): void {
                $builder->where('source', $source)
                    ->orWhere(function (Builder $fallback) use ($source): void {
                        $fallback->where(function (Builder $missingSource): void {
                            $missingSource->whereNull('source')->orWhere('source', '');
                        })->where('utm_source', $source);
                    });
            });

            return;
        }

        $query->where(function (Builder $builder) use ($source): void {
            $builder->where('utm_source', $source)
                ->orWhere(function (Builder $fallback) use ($source): void {
                    $fallback->where(function (Builder $missingUtm): void {
                        $missingUtm->whereNull('utm_source')->orWhere('utm_source', '');
                    })->where('source', $source);
                });
        });
    }

    private function filterForCampaign(MarketingAnalyticsFilter $filter, int $campaignId): MarketingAnalyticsFilter
    {
        return new MarketingAnalyticsFilter(
            start: $filter->start,
            end: $filter->end,
            previousStart: $filter->previousStart,
            previousEnd: $filter->previousEnd,
            period: $filter->period,
            campaignId: $campaignId,
            campaignStatus: null,
            source: $filter->source,
        );
    }

    private function withoutSource(MarketingAnalyticsFilter $filter): MarketingAnalyticsFilter
    {
        return new MarketingAnalyticsFilter(
            start: $filter->start,
            end: $filter->end,
            previousStart: $filter->previousStart,
            previousEnd: $filter->previousEnd,
            period: $filter->period,
            campaignId: $filter->campaignId,
            campaignStatus: $filter->campaignStatus,
            source: null,
        );
    }

    private function delta(int|float $current, int|float $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    /** @param array<string, mixed> $metrics @param array<int, string> $keys */
    private function metricInt(array $metrics, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (isset($metrics[$key]) && is_numeric($metrics[$key])) {
                return (int) $metrics[$key];
            }
        }

        return null;
    }

    /** @param array<string, mixed> $metrics @param array<int, string> $keys */
    private function metricFloat(array $metrics, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (isset($metrics[$key]) && is_numeric($metrics[$key])) {
                return round((float) $metrics[$key], 2);
            }
        }

        return null;
    }

    private function numericAmount(string $amount): float
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $amount)) ?? '0';

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
