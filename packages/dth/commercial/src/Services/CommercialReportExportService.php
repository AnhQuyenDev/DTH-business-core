<?php

namespace Dth\Commercial\Services;

use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Models\Opportunity;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Models\ServicePackage;
use Dth\Commercial\Support\SimplePdfWriter;
use Dth\Commercial\Support\SimpleXlsxWriter;
use Dth\Commercial\Support\UiText;
use InvalidArgumentException;

final class CommercialReportExportService
{
    public function __construct(
        private readonly SimpleXlsxWriter $xlsx,
        private readonly SimplePdfWriter $pdf,
        private readonly CommercialInsightService $insightService,
    ) {}

    /** @return array{content:string,mime:string,extension:string} */
    public function dashboard(string $format): array
    {
        $snapshot = app(CommercialAnalyticsService::class)->snapshot();
        $insights = $this->insightService->insights($snapshot);

        return $this->build(
            $format,
            UiText::get('reports.title', 'Commercial Report'),
            $this->sheets($snapshot, $insights),
            $this->pdfLines($snapshot, $insights),
        );
    }

    /** @param array<string, array<int, array<int, mixed>>> $sheets @param array<int, string> $pdfLines @return array{content:string,mime:string,extension:string} */
    private function build(string $format, string $title, array $sheets, array $pdfLines): array
    {
        return match (strtolower($format)) {
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
            default => throw new InvalidArgumentException('Unsupported Commercial report export format.'),
        };
    }

    /** @param array<string, mixed> $snapshot @param array<int, array<string, string>> $insights @return array<string, array<int, array<int, mixed>>> */
    private function sheets(array $snapshot, array $insights): array
    {
        $sheets = [
            UiText::get('reports.sections.summary', 'Summary') => [
                [UiText::get('reports.columns.metric', 'Metric'), UiText::get('reports.columns.value', 'Value')],
                [UiText::get('overview.active_services', 'Active services'), (int) ($snapshot['active_services'] ?? 0)],
                [UiText::get('overview.active_packages', 'Active packages'), (int) ($snapshot['active_packages'] ?? 0)],
                [UiText::get('overview.open_opportunities', 'Open opportunities'), (int) ($snapshot['open_opportunities'] ?? 0)],
                [UiText::get('overview.pipeline_value', 'Pipeline value'), (float) ($snapshot['pipeline_value'] ?? 0)],
                [UiText::get('overview.weighted_pipeline', 'Weighted pipeline'), (float) ($snapshot['weighted_pipeline_value'] ?? 0)],
                [UiText::get('overview.win_rate', 'Win rate').' (%)', (float) ($snapshot['win_rate'] ?? 0)],
                [UiText::get('overview.closing_this_month', 'Closing this month'), (int) ($snapshot['closing_this_month_count'] ?? 0)],
                [UiText::get('reports.metrics.closing_value', 'Closing value this month'), (float) ($snapshot['closing_this_month_value'] ?? 0)],
                [UiText::get('reports.metrics.generated_at', 'Generated at'), now()->format('d/m/Y H:i:s')],
            ],
            UiText::get('reports.sections.pipeline_stages', 'Pipeline stages') => [[UiText::get('reports.columns.stage', 'Stage'), UiText::get('reports.columns.opportunities', 'Opportunities'), UiText::get('reports.columns.share', 'Share (%)'), UiText::get('reports.columns.value', 'Value')]],
            UiText::get('reports.sections.services', 'Services') => [[UiText::get('reports.columns.service_code', 'Service code'), UiText::get('reports.columns.name', 'Name'), UiText::get('reports.columns.reference', 'Reference'), UiText::get('reports.columns.status', 'Status'), UiText::get('reports.columns.packages', 'Packages'), UiText::get('reports.columns.updated_at', 'Updated at')]],
            UiText::get('reports.sections.packages', 'Service packages') => [[UiText::get('reports.columns.package_code', 'Package code'), UiText::get('reports.columns.service', 'Service'), UiText::get('reports.columns.name', 'Name'), UiText::get('reports.columns.audience', 'Audience'), UiText::get('reports.columns.billing_period', 'Billing period'), UiText::get('reports.columns.unit', 'Unit'), UiText::get('reports.columns.default_quantity', 'Default quantity'), UiText::get('reports.columns.status', 'Status')]],
            UiText::get('reports.sections.opportunities', 'Opportunities') => [[UiText::get('reports.columns.opportunity_code', 'Opportunity code'), UiText::get('reports.columns.name', 'Name'), UiText::get('reports.columns.customer', 'Company / Contact'), UiText::get('reports.columns.service', 'Service'), UiText::get('reports.columns.stage', 'Stage'), UiText::get('reports.columns.estimated_value', 'Estimated value'), UiText::get('reports.columns.probability', 'Probability (%)'), UiText::get('reports.columns.weighted_value', 'Weighted value'), UiText::get('reports.columns.expected_close', 'Expected close'), UiText::get('reports.columns.owner', 'Owner'), UiText::get('reports.columns.updated_at', 'Updated at')]],
            UiText::get('reports.sections.top_services', 'Top services') => [[UiText::get('reports.columns.service', 'Service'), UiText::get('reports.columns.count', 'Count'), UiText::get('reports.columns.pipeline_value', 'Pipeline value')]],
            UiText::get('reports.sections.insights', 'Insights') => [[UiText::get('reports.columns.level', 'Level'), UiText::get('reports.columns.name', 'Title'), UiText::get('reports.columns.metric', 'Metric'), UiText::get('reports.columns.analysis', 'Analysis')]],
        ];

        foreach ((array) ($snapshot['stage_breakdown'] ?? []) as $row) {
            $sheets[UiText::get('reports.sections.pipeline_stages', 'Pipeline stages')][] = [
                $row['label'] ?? '',
                $row['count'] ?? 0,
                $row['percentage'] ?? 0,
                $row['value'] ?? 0,
            ];
        }

        Service::query()->withCount('packages')->orderBy('name')->chunk(250, function ($records) use (&$sheets): void {
            foreach ($records as $record) {
                $sheets[UiText::get('reports.sections.services', 'Services')][] = [
                    $record->service_code,
                    $record->name,
                    $record->reference(),
                    $this->enumValue($record->status),
                    $record->packages_count,
                    $record->updated_at?->format('d/m/Y H:i:s'),
                ];
            }
        });

        ServicePackage::query()->with('service:id,name')->orderBy('name')->chunk(250, function ($records) use (&$sheets): void {
            foreach ($records as $record) {
                $billingPeriod = trim(implode(' ', array_filter([
                    $record->billing_period,
                    $this->enumValue($record->billing_period_unit),
                ], static fn ($value): bool => filled($value))));

                $sheets[UiText::get('reports.sections.packages', 'Service packages')][] = [
                    $record->package_code,
                    $record->service?->name,
                    $record->name,
                    $this->enumValue($record->audience_type),
                    $billingPeriod,
                    $record->unit,
                    $record->default_quantity,
                    $this->enumValue($record->status),
                ];
            }
        });

        Opportunity::query()->latest('updated_at')->chunk(250, function ($records) use (&$sheets): void {
            foreach ($records as $record) {
                $value = (float) ($record->estimated_value ?? 0);
                $probability = (int) ($record->probability ?? 0);
                $stage = $record->stage instanceof OpportunityStage
                    ? $record->stage->label()
                    : (OpportunityStage::tryFrom((string) $record->stage)?->label() ?? (string) $record->stage);

                $sheets[UiText::get('reports.sections.opportunities', 'Opportunities')][] = [
                    $record->opportunity_code,
                    $record->title,
                    $record->company_name_snapshot ?: $record->contact_name_snapshot,
                    $record->service_name_snapshot,
                    $stage,
                    $value,
                    $probability,
                    round($value * ($probability / 100), 2),
                    $record->expected_close_date?->format('d/m/Y'),
                    $record->assigned_employee_name_snapshot,
                    $record->updated_at?->format('d/m/Y H:i:s'),
                ];
            }
        });

        foreach ((array) ($snapshot['top_services'] ?? []) as $row) {
            $sheets[UiText::get('reports.sections.top_services', 'Top services')][] = [$row['name'] ?? '', $row['count'] ?? 0, $row['value'] ?? 0];
        }

        foreach ($insights as $row) {
            $sheets[UiText::get('reports.sections.insights', 'Insights')][] = [$this->insightLevelLabel((string) ($row['level'] ?? 'neutral')), $row['title'] ?? '', $row['metric'] ?? '', $row['body'] ?? ''];
        }

        return $sheets;
    }

    /** @param array<string, array<int, array<int, mixed>>> $sheets */
    private function csv(array $sheets): string
    {
        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            throw new \RuntimeException('Unable to create Commercial CSV stream.');
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

    /** @param array<string, mixed> $snapshot @param array<int, array<string, string>> $insights @return array<int, string> */
    private function pdfLines(array $snapshot, array $insights): array
    {
        $lines = [
            UiText::get('reports.metrics.generated_at', 'Generated at').': '.now()->format('d/m/Y H:i:s'),
            '',
            mb_strtoupper(UiText::get('reports.sections.summary', 'Summary')),
            UiText::get('overview.active_services', 'Active services').': '.(int) ($snapshot['active_services'] ?? 0),
            UiText::get('overview.active_packages', 'Active packages').': '.(int) ($snapshot['active_packages'] ?? 0),
            UiText::get('overview.open_opportunities', 'Open opportunities').': '.(int) ($snapshot['open_opportunities'] ?? 0),
            UiText::get('overview.pipeline_value', 'Pipeline value').': '.number_format((float) ($snapshot['pipeline_value'] ?? 0), 0, '.', ',').' VND',
            UiText::get('overview.weighted_pipeline', 'Weighted pipeline').': '.number_format((float) ($snapshot['weighted_pipeline_value'] ?? 0), 0, '.', ',').' VND',
            UiText::get('overview.win_rate', 'Win rate').': '.number_format((float) ($snapshot['win_rate'] ?? 0), 1).'%',
            UiText::get('overview.closing_this_month', 'Closing this month').': '.(int) ($snapshot['closing_this_month_count'] ?? 0).' / '.number_format((float) ($snapshot['closing_this_month_value'] ?? 0), 0, '.', ',').' VND',
            '',
            mb_strtoupper(UiText::get('reports.sections.pipeline_stages', 'Pipeline stages')),
        ];

        foreach ((array) ($snapshot['stage_breakdown'] ?? []) as $row) {
            $lines[] = sprintf(
                '%s: %d | %.0f%% | %s VND',
                (string) ($row['label'] ?? ''),
                (int) ($row['count'] ?? 0),
                (float) ($row['percentage'] ?? 0),
                number_format((float) ($row['value'] ?? 0), 0, '.', ','),
            );
        }

        $lines[] = '';
        $lines[] = mb_strtoupper(UiText::get('reports.sections.top_services', 'Top services'));
        foreach ((array) ($snapshot['top_services'] ?? []) as $row) {
            $lines[] = sprintf(
                '%s: %d | %s VND',
                (string) ($row['name'] ?? ''),
                (int) ($row['count'] ?? 0),
                number_format((float) ($row['value'] ?? 0), 0, '.', ','),
            );
        }

        $lines[] = '';
        $lines[] = mb_strtoupper(UiText::get('reports.sections.insights', 'Statistical analysis'));
        foreach ($insights as $row) {
            $lines[] = $this->insightLevelLabel((string) ($row['level'] ?? 'neutral')).' - '.(string) ($row['title'] ?? '');
            $lines[] = (string) ($row['body'] ?? '');
        }

        return $lines;
    }

    private function insightLevelLabel(string $level): string
    {
        return UiText::get('reports.levels.'.$level, ucfirst($level));
    }

    private function enumValue(mixed $value): string
    {
        if (! $value instanceof \BackedEnum) {
            return (string) ($value ?? '');
        }

        $class = $value::class;
        if (method_exists($class, 'options')) {
            $options = $class::options();

            return (string) ($options[$value->value] ?? $value->value);
        }

        return (string) $value->value;
    }
}
