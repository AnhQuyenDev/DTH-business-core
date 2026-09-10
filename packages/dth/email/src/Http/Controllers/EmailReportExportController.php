<?php

namespace Dth\Email\Http\Controllers;

use Dth\Email\Filament\Pages\EmailDashboard;
use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Services\EmailDashboardFilterResolver;
use Dth\Email\Services\EmailReportDataService;
use Dth\Email\Services\EmailReportPdfService;
use Dth\Email\Services\EmailReportSpreadsheetService;
use Dth\Email\Support\UiText;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmailReportExportController
{
    public function dashboardPdf(
        Request $request,
        EmailDashboardFilterResolver $filters,
        EmailReportDataService $reports,
        EmailReportPdfService $pdf,
    ): Response {
        $this->syncLocale($request);
        $this->authorizeDashboard();

        $resolved = $filters->resolveRequest($request);
        $content = $pdf->dashboard($reports->dashboard($resolved));

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="email-analytics-'.now()->format('Ymd-His').'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function dashboardCsv(
        Request $request,
        EmailDashboardFilterResolver $filters,
        EmailReportSpreadsheetService $spreadsheets,
    ): StreamedResponse {
        $this->syncLocale($request);
        $this->authorizeDashboard();

        $resolved = $filters->resolveRequest($request);

        return $spreadsheets->csvDownload(
            filename: 'email-campaigns-'.now()->format('Ymd-His').'.csv',
            headers: $spreadsheets->dashboardHeaders(),
            rows: $spreadsheets->dashboardRows($resolved),
        );
    }

    public function dashboardXlsx(
        Request $request,
        EmailDashboardFilterResolver $filters,
        EmailReportSpreadsheetService $spreadsheets,
    ): BinaryFileResponse {
        $this->syncLocale($request);
        $this->authorizeDashboard();

        $resolved = $filters->resolveRequest($request);

        return $spreadsheets->xlsxDownload(
            filename: 'email-campaigns-'.now()->format('Ymd-His').'.xlsx',
            headers: $spreadsheets->dashboardHeaders(),
            rows: $spreadsheets->dashboardRows($resolved),
            columnWidths: [
                1 => 8,
                2 => 28,
                3 => 38,
                4 => 16,
                5 => 20,
                6 => 14,
                7 => 12,
                8 => 15,
                9 => 15,
                10 => 15,
                11 => 15,
                12 => 12,
                13 => 16,
                14 => 19,
                15 => 12,
                16 => 16,
            ],
            sheetName: UiText::get('reports.campaigns_export', 'Campaigns'),
        );
    }

    public function campaignPdf(
        Request $request,
        EmailCampaign $campaign,
        EmailReportDataService $reports,
        EmailReportPdfService $pdf,
    ): Response {
        $this->syncLocale($request);
        $this->authorizeCampaign($campaign);

        $content = $pdf->campaign($reports->campaign($campaign));
        $filename = $this->campaignFilename($campaign, 'report', 'pdf');

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function campaignCsv(
        Request $request,
        EmailCampaign $campaign,
        EmailReportSpreadsheetService $spreadsheets,
    ): StreamedResponse {
        $this->syncLocale($request);
        $this->authorizeCampaign($campaign);
        $spreadsheets->assertCampaignRecipientExportAllowed($campaign);

        return $spreadsheets->csvDownload(
            filename: $this->campaignFilename($campaign, 'recipients', 'csv'),
            headers: $spreadsheets->campaignRecipientHeaders(),
            rows: $spreadsheets->campaignRecipientRows($campaign),
        );
    }

    public function campaignXlsx(
        Request $request,
        EmailCampaign $campaign,
        EmailReportSpreadsheetService $spreadsheets,
    ): BinaryFileResponse {
        $this->syncLocale($request);
        $this->authorizeCampaign($campaign);
        $spreadsheets->assertCampaignRecipientExportAllowed($campaign);

        return $spreadsheets->xlsxDownload(
            filename: $this->campaignFilename($campaign, 'recipients', 'xlsx'),
            headers: $spreadsheets->campaignRecipientHeaders(),
            rows: $spreadsheets->campaignRecipientRows($campaign),
            columnWidths: [
                1 => 8,
                2 => 32,
                3 => 24,
                4 => 16,
                5 => 21,
                6 => 21,
                7 => 21,
                8 => 21,
                9 => 38,
                10 => 38,
                11 => 18,
                12 => 36,
            ],
            sheetName: UiText::get('reports.recipients_export', 'Recipients'),
        );
    }

    private function authorizeDashboard(): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(EmailDashboard::canAccess(), 403);
    }

    private function authorizeCampaign(EmailCampaign $campaign): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(EmailCampaignResource::canView($campaign), 403);
    }

    private function syncLocale(Request $request): void
    {
        $locale = $request->hasSession()
            ? $request->session()->get('locale')
            : null;
        $locale ??= $request->user()?->preferred_locale;

        if (
            is_string($locale)
            && array_key_exists($locale, (array) config('localization.supported', []))
        ) {
            app()->setLocale($locale);
        }
    }

    private function campaignFilename(EmailCampaign $campaign, string $suffix, string $extension): string
    {
        $slug = Str::slug($campaign->name) ?: 'campaign-'.$campaign->getKey();

        return sprintf(
            '%s-%s-%s.%s',
            $slug,
            $suffix,
            now()->format('Ymd-His'),
            $extension,
        );
    }
}
