<?php

namespace Dth\Marketing\Http\Controllers;

use Dth\Marketing\DTO\MarketingAnalyticsFilter;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Services\MarketingAnalyticsService;
use Dth\Marketing\Services\MarketingAuditTrailService;
use Dth\Marketing\Services\MarketingReportExportService;
use Dth\Marketing\Support\MarketingAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

final class MarketingReportExportController
{
    public function dashboard(
        Request $request,
        string $format,
        MarketingAnalyticsService $analytics,
        MarketingReportExportService $exports,
        MarketingAuthorizationService $authorization,
        MarketingAuditTrailService $audit,
    ): Response {
        abort_unless($authorization->reports($request->user()) && $authorization->export($request->user()), 403);
        $this->assertFormat($format);

        $filter = MarketingAnalyticsFilter::fromArray($request->query());
        $data = $analytics->dashboard($filter);
        $file = $exports->dashboard($format, $data);
        $audit->log('report.dashboard_exported', metadata: [
            'format' => $format,
            'filters' => $filter->toQuery(),
        ]);

        return $this->download(
            $file['content'],
            'marketing-analytics-'.now()->format('Ymd-His').'.'.$file['extension'],
            $file['mime'],
        );
    }

    public function campaign(
        Request $request,
        MarketingCampaign $campaign,
        string $format,
        MarketingAnalyticsService $analytics,
        MarketingReportExportService $exports,
        MarketingAuthorizationService $authorization,
        MarketingAuditTrailService $audit,
    ): Response {
        abort_unless($authorization->reports($request->user()) && $authorization->export($request->user()), 403);
        $this->assertFormat($format);

        $filter = MarketingAnalyticsFilter::fromArray($request->query());
        $data = $analytics->campaignReport($campaign, $filter);
        $file = $exports->campaign($format, $data);
        $audit->log('report.campaign_exported', $campaign, metadata: [
            'format' => $format,
            'filters' => $filter->toQuery(),
        ]);
        $slug = Str::slug((string) $campaign->name) ?: 'campaign-'.$campaign->getKey();

        return $this->download(
            $file['content'],
            'marketing-campaign-'.$slug.'-'.now()->format('Ymd-His').'.'.$file['extension'],
            $file['mime'],
        );
    }

    private function assertFormat(string $format): void
    {
        abort_unless(in_array(strtolower($format), ['pdf', 'xlsx', 'csv'], true), 404);
    }

    private function download(string $content, string $filename, string $mime): Response
    {
        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($content),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
