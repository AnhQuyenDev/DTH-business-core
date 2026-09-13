<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Support\SimplePdfWriter;
use Dth\Marketing\Support\SimpleXlsxWriter;
use InvalidArgumentException;

final class MarketingReportExportService
{
    public function __construct(
        private readonly SimpleXlsxWriter $xlsx,
        private readonly SimplePdfWriter $pdf,
    ) {}

    /** @param array<string, mixed> $analytics @return array{content:string,mime:string,extension:string} */
    public function dashboard(string $format, array $analytics): array
    {
        return $this->build(
            $format,
            'Marketing Analytics',
            $this->sheets($analytics),
            $this->pdfLines($analytics),
        );
    }

    /** @param array<string, mixed> $report @return array{content:string,mime:string,extension:string} */
    public function campaign(string $format, array $report): array
    {
        $campaign = $report['campaign'] ?? null;
        $name = is_object($campaign) && isset($campaign->name) ? (string) $campaign->name : 'Campaign';

        return $this->build(
            $format,
            'Marketing Campaign Report - '.$name,
            $this->sheets($report),
            $this->pdfLines($report, true),
        );
    }

    /** @param array<string, array<int, array<int, mixed>>> $sheets @param array<int, string> $pdfLines @return array{content:string,mime:string,extension:string} */
    private function build(string $format, string $title, array $sheets, array $pdfLines): array
    {
        $format = strtolower($format);

        return match ($format) {
            'csv' => [
                'content' => $this->csv($sheets),
                'mime' => 'text/csv; charset=UTF-8',
                'extension' => 'csv',
            ],
            'xlsx' => [
                'content' => $this->xlsx->build($sheets),
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extension' => 'xlsx',
            ],
            'pdf' => [
                'content' => $this->pdf->build($title, $pdfLines),
                'mime' => 'application/pdf',
                'extension' => 'pdf',
            ],
            default => throw new InvalidArgumentException('Unsupported report export format.'),
        };
    }

    /** @param array<string, mixed> $analytics @return array<string, array<int, array<int, mixed>>> */
    private function sheets(array $analytics): array
    {
        $summary = (array) ($analytics['summary'] ?? []);
        $filter = $analytics['filter'] ?? null;
        $range = is_object($filter) && method_exists($filter, 'label') ? $filter->label() : '';

        $sheets = [
            'Summary' => [
                ['Metric', 'Value'],
                ['Period', $range],
                ['Views', $summary['views'] ?? 0],
                ['Submissions', $summary['submissions'] ?? 0],
                ['Leads', ($summary['leads_available'] ?? false) ? ($summary['leads'] ?? 0) : 'N/A'],
                ['Customers', ($summary['financial_available'] ?? false) ? ($summary['customers'] ?? 0) : 'N/A'],
                ['Budget', $summary['budget'] ?? 0],
                ['Revenue', ($summary['financial_available'] ?? false) ? ($summary['revenue'] ?? 0) : 'N/A'],
                ['ROAS', ($summary['financial_available'] ?? false) ? ($summary['roas'] ?? 'N/A') : 'N/A'],
                ['View -> Submission %', $summary['view_to_submission'] ?? 0],
                ['Submission -> Lead %', ($summary['leads_available'] ?? false) ? ($summary['submission_to_lead'] ?? 0) : 'N/A'],
                ['Lead -> Customer %', ($summary['financial_available'] ?? false) && ($summary['leads_available'] ?? false) ? ($summary['lead_to_customer'] ?? 0) : 'N/A'],
                ['Failed submissions', $summary['failed'] ?? 0],
                ['Spam submissions', $summary['spam'] ?? 0],
            ],
        ];

        $sheets['Campaigns'] = [['Campaign', 'Status', 'Views', 'Submissions', 'Leads', 'Customers', 'Budget', 'Revenue', 'ROAS']];
        foreach ((array) ($analytics['campaigns'] ?? []) as $row) {
            $sheets['Campaigns'][] = [
                $row['name'] ?? '',
                $row['status'] ?? '',
                $row['views'] ?? 0,
                $row['submissions'] ?? 0,
                $row['leads'] ?? 'N/A',
                $row['customers'] ?? 'N/A',
                $row['budget'] ?? 0,
                $row['revenue'] ?? 'N/A',
                $row['roas'] ?? 'N/A',
            ];
        }

        $sheets['Landing Pages'] = [['Landing Page', 'Status', 'Views', 'Submissions', 'Leads', 'Conversion %']];
        foreach ((array) ($analytics['landing_pages'] ?? []) as $row) {
            $sheets['Landing Pages'][] = [
                $row['name'] ?? '',
                $row['status'] ?? '',
                $row['views'] ?? 0,
                $row['submissions'] ?? 0,
                $row['leads'] ?? 'N/A',
                $row['conversion_rate'] ?? 0,
            ];
        }

        $sheets['Sources'] = [['Source', 'Views', 'Submissions', 'Leads', 'Conversion %']];
        foreach ((array) ($analytics['sources'] ?? []) as $row) {
            $sheets['Sources'][] = [
                $row['source'] ?? '',
                $row['views'] ?? 0,
                $row['submissions'] ?? 0,
                $row['leads'] ?? 'N/A',
                $row['conversion_rate'] ?? 'N/A',
            ];
        }

        $sheets['UTM Medium'] = [['Medium', 'Views', 'Submissions', 'Leads', 'Conversion %']];
        foreach ((array) ($analytics['utm_mediums'] ?? []) as $row) {
            $sheets['UTM Medium'][] = [
                $row['medium'] ?? '',
                $row['views'] ?? 0,
                $row['submissions'] ?? 0,
                $row['leads'] ?? 'N/A',
                $row['conversion_rate'] ?? 'N/A',
            ];
        }

        $sheets['UTM Campaign'] = [['UTM Campaign', 'Views', 'Submissions', 'Leads', 'Conversion %']];
        foreach ((array) ($analytics['utm_campaigns'] ?? []) as $row) {
            $sheets['UTM Campaign'][] = [
                $row['campaign'] ?? '',
                $row['views'] ?? 0,
                $row['submissions'] ?? 0,
                $row['leads'] ?? 'N/A',
                $row['conversion_rate'] ?? 'N/A',
            ];
        }

        $sheets['Trend'] = [['Date', 'Views', 'Submissions', 'Leads']];
        foreach ((array) ($analytics['trend'] ?? []) as $row) {
            $sheets['Trend'][] = [
                $row['date'] ?? '',
                $row['views'] ?? 0,
                $row['submissions'] ?? 0,
                $row['leads'] ?? 'N/A',
            ];
        }

        $sheets['Email Campaigns'] = [['Email Campaign', 'Status', 'Sent', 'Opened', 'Clicked', 'Open %', 'Click %']];
        foreach ((array) ($analytics['email_campaigns'] ?? []) as $row) {
            $sheets['Email Campaigns'][] = [
                $row['name'] ?? '',
                $row['status'] ?? '',
                $row['sent'] ?? 'N/A',
                $row['opened'] ?? 'N/A',
                $row['clicked'] ?? 'N/A',
                $row['open_rate'] ?? 'N/A',
                $row['click_rate'] ?? 'N/A',
            ];
        }

        if (isset($analytics['utm_links'])) {
            $sheets['UTM Links'] = [['Landing Page', 'Name', 'Source', 'Medium', 'Campaign', 'URL']];
            foreach ((array) $analytics['utm_links'] as $row) {
                $sheets['UTM Links'][] = [
                    $row['landing_page'] ?? '',
                    $row['name'] ?? '',
                    $row['source'] ?? '',
                    $row['medium'] ?? '',
                    $row['campaign'] ?? '',
                    $row['url'] ?? '',
                ];
            }
        }

        $sheets['Insights'] = [['Level', 'Title', 'Insight']];
        foreach ((array) ($analytics['insights'] ?? []) as $row) {
            $sheets['Insights'][] = [$row['level'] ?? '', $row['title'] ?? '', $row['body'] ?? ''];
        }

        return $sheets;
    }

    /** @param array<string, array<int, array<int, mixed>>> $sheets */
    private function csv(array $sheets): string
    {
        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            throw new \RuntimeException('Unable to create CSV stream.');
        }
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ($sheets as $title => $rows) {
            fputcsv($stream, [$title]);
            foreach ($rows as $row) {
                fputcsv($stream, $row);
            }
            fputcsv($stream, []);
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return is_string($content) ? $content : '';
    }

    /** @param array<string, mixed> $analytics @return array<int, string> */
    private function pdfLines(array $analytics, bool $campaign = false): array
    {
        $summary = (array) ($analytics['summary'] ?? []);
        $filter = $analytics['filter'] ?? null;
        $lines = [];
        if ($campaign && isset($analytics['campaign']) && is_object($analytics['campaign'])) {
            $lines[] = 'Campaign: '.(string) ($analytics['campaign']->name ?? '');
            $lines[] = 'Status: '.(string) (($analytics['campaign']->status instanceof \BackedEnum) ? $analytics['campaign']->status->value : ($analytics['campaign']->status ?? ''));
        }
        $lines[] = 'Period: '.(is_object($filter) && method_exists($filter, 'label') ? $filter->label() : '');
        $lines[] = '';
        $lines[] = 'KPI SUMMARY';
        $lines[] = 'Views: '.(int) ($summary['views'] ?? 0);
        $lines[] = 'Submissions: '.(int) ($summary['submissions'] ?? 0);
        $lines[] = 'Leads: '.(($summary['leads_available'] ?? false) ? (string) ($summary['leads'] ?? 0) : 'N/A');
        $lines[] = 'Customers: '.(($summary['financial_available'] ?? false) ? (string) ($summary['customers'] ?? 0) : 'N/A');
        $lines[] = 'Budget: '.number_format((float) ($summary['budget'] ?? 0), 0, '.', ',').' '.($summary['currency'] ?? 'VND');
        $lines[] = 'Revenue: '.(($summary['financial_available'] ?? false) ? number_format((float) ($summary['revenue'] ?? 0), 0, '.', ',').' '.($summary['currency'] ?? 'VND') : 'N/A');
        $lines[] = 'ROAS: '.(($summary['roas'] ?? null) !== null ? number_format((float) $summary['roas'], 2).'x' : 'N/A');
        $lines[] = 'View -> Submission: '.number_format((float) ($summary['view_to_submission'] ?? 0), 2).'%';
        $lines[] = '';
        $lines[] = 'TOP SOURCES';
        foreach (array_slice((array) ($analytics['sources'] ?? []), 0, 8) as $row) {
            $lines[] = sprintf('%s | views %d | submissions %d | conversion %s',
                (string) ($row['source'] ?? 'direct'),
                (int) ($row['views'] ?? 0),
                (int) ($row['submissions'] ?? 0),
                ($row['conversion_rate'] ?? null) === null ? 'N/A' : number_format((float) $row['conversion_rate'], 2).'%',
            );
        }
        $lines[] = '';
        $lines[] = 'UTM MEDIUM ATTRIBUTION';
        foreach (array_slice((array) ($analytics['utm_mediums'] ?? []), 0, 8) as $row) {
            $lines[] = sprintf('%s | views %d | submissions %d | conversion %s',
                (string) ($row['medium'] ?? '(none)'),
                (int) ($row['views'] ?? 0),
                (int) ($row['submissions'] ?? 0),
                ($row['conversion_rate'] ?? null) === null ? 'N/A' : number_format((float) $row['conversion_rate'], 2).'%',
            );
        }
        $lines[] = '';
        $lines[] = 'UTM CAMPAIGN ATTRIBUTION';
        foreach (array_slice((array) ($analytics['utm_campaigns'] ?? []), 0, 8) as $row) {
            $lines[] = sprintf('%s | views %d | submissions %d | conversion %s',
                (string) ($row['campaign'] ?? '(none)'),
                (int) ($row['views'] ?? 0),
                (int) ($row['submissions'] ?? 0),
                ($row['conversion_rate'] ?? null) === null ? 'N/A' : number_format((float) $row['conversion_rate'], 2).'%',
            );
        }
        $lines[] = '';
        $lines[] = 'LANDING PAGES';
        foreach (array_slice((array) ($analytics['landing_pages'] ?? []), 0, 10) as $row) {
            $lines[] = sprintf('%s | views %d | submissions %d | conversion %.2f%%',
                (string) ($row['name'] ?? ''),
                (int) ($row['views'] ?? 0),
                (int) ($row['submissions'] ?? 0),
                (float) ($row['conversion_rate'] ?? 0),
            );
        }
        if (! $campaign) {
            $lines[] = '';
            $lines[] = 'MARKETING CAMPAIGNS';
            foreach (array_slice((array) ($analytics['campaigns'] ?? []), 0, 10) as $row) {
                $lines[] = sprintf('%s | %s | views %d | submissions %d | ROAS %s',
                    (string) ($row['name'] ?? ''),
                    (string) ($row['status'] ?? ''),
                    (int) ($row['views'] ?? 0),
                    (int) ($row['submissions'] ?? 0),
                    ($row['roas'] ?? null) === null ? 'N/A' : number_format((float) $row['roas'], 2).'x',
                );
            }
        }
        $lines[] = '';
        $lines[] = 'EMAIL CAMPAIGNS';
        foreach (array_slice((array) ($analytics['email_campaigns'] ?? []), 0, 10) as $row) {
            $lines[] = sprintf('%s | %s | sent %s | open %s | click %s',
                (string) ($row['name'] ?? ''),
                (string) ($row['status'] ?? 'N/A'),
                ($row['sent'] ?? null) === null ? 'N/A' : (string) $row['sent'],
                ($row['open_rate'] ?? null) === null ? 'N/A' : number_format((float) $row['open_rate'], 2).'%',
                ($row['click_rate'] ?? null) === null ? 'N/A' : number_format((float) $row['click_rate'], 2).'%',
            );
        }
        if ($campaign && isset($analytics['utm_links'])) {
            $lines[] = '';
            $lines[] = 'GENERATED UTM LINKS';
            foreach (array_slice((array) $analytics['utm_links'], 0, 10) as $row) {
                $lines[] = sprintf('%s | %s | %s | %s',
                    (string) ($row['landing_page'] ?? ''),
                    (string) ($row['source'] ?? ''),
                    (string) ($row['medium'] ?? ''),
                    (string) ($row['url'] ?? ''),
                );
            }
        }
        $lines[] = '';
        $lines[] = 'STATISTICAL INSIGHTS';
        foreach ((array) ($analytics['insights'] ?? []) as $row) {
            $lines[] = strtoupper((string) ($row['level'] ?? 'INFO')).' - '.(string) ($row['title'] ?? '').': '.(string) ($row['body'] ?? '');
        }

        return $lines;
    }
}
