<?php

namespace Dth\Email\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Dth\Email\Enums\AnalyticsGranularity;
use Dth\Email\Support\ReportChartBuilder;
use Dth\Email\Support\UiText;
use RuntimeException;

class EmailReportPdfService
{
    public function __construct(
        private readonly ReportChartBuilder $charts,
    ) {}

    /** @param array<string, mixed> $report */
    public function dashboard(array $report): string
    {
        $granularity = $report['granularity'];
        $trend = $report['trend'];
        $report['trendChart'] = $this->charts->lineChartDataUri(
            labels: array_map(
                static fn ($point): string => $granularity === AnalyticsGranularity::Hour
                    ? $point->bucket->format('d/m H:i')
                    : $point->bucket->format('d/m'),
                $trend,
            ),
            series: [
                [
                    'label' => UiText::get('dashboard.metrics.sent', 'Sent'),
                    'values' => array_map(static fn ($point): int => $point->sent, $trend),
                    'color' => '#f59e0b',
                ],
                [
                    'label' => UiText::get('dashboard.metrics.unique_opens', 'Unique opens'),
                    'values' => array_map(static fn ($point): int => $point->uniqueOpened, $trend),
                    'color' => '#3b82f6',
                ],
                [
                    'label' => UiText::get('dashboard.metrics.unique_clicks', 'Unique clicks'),
                    'values' => array_map(static fn ($point): int => $point->uniqueClicked, $trend),
                    'color' => '#10b981',
                ],
            ],
        );

        return $this->render('dth-email::reports.dashboard-pdf', $report);
    }

    /** @param array<string, mixed> $report */
    public function campaign(array $report): string
    {
        $granularity = $report['trendGranularity'];
        $trend = $report['trend'];
        $report['trendChart'] = $this->charts->lineChartDataUri(
            labels: array_map(
                static fn ($point): string => $granularity === AnalyticsGranularity::Hour
                    ? $point->bucket->format('d/m H:i')
                    : $point->bucket->format('d/m'),
                $trend,
            ),
            series: [
                [
                    'label' => UiText::get('dashboard.metrics.sent', 'Sent'),
                    'values' => array_map(static fn ($point): int => $point->sent, $trend),
                    'color' => '#f59e0b',
                ],
                [
                    'label' => UiText::get('dashboard.metrics.unique_opens', 'Unique opens'),
                    'values' => array_map(static fn ($point): int => $point->uniqueOpened, $trend),
                    'color' => '#3b82f6',
                ],
                [
                    'label' => UiText::get('dashboard.metrics.unique_clicks', 'Unique clicks'),
                    'values' => array_map(static fn ($point): int => $point->uniqueClicked, $trend),
                    'color' => '#10b981',
                ],
            ],
        );

        return $this->render('dth-email::reports.campaign-pdf', $report);
    }

    /** @param array<string, mixed> $data */
    private function render(string $view, array $data): string
    {
        if (! (bool) config('dth-email.reports.pdf.enabled', true)) {
            throw new RuntimeException(UiText::get(
                'reports.errors.pdf_disabled',
                'PDF reports are disabled by configuration.'
            ));
        }

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper(
            (string) config('dth-email.reports.pdf.paper', 'a4'),
            (string) config('dth-email.reports.pdf.orientation', 'landscape'),
        );
        $dompdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }
}
