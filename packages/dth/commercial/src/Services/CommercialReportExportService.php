<?php

namespace Dth\Commercial\Services;

use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Models\Bundle;
use Dth\Commercial\Models\BundleItem;
use Dth\Commercial\Models\Opportunity;
use Dth\Commercial\Models\OpportunityItem;
use Dth\Commercial\Models\Product;
use Dth\Commercial\Models\Service;
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
            'csv' => ['content' => $this->csv($sheets), 'mime' => 'text/csv; charset=UTF-8', 'extension' => 'csv'],
            'xlsx' => ['content' => $this->xlsx->build($sheets), 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'extension' => 'xlsx'],
            'pdf' => ['content' => $this->pdf->build($title, $pdfLines), 'mime' => 'application/pdf', 'extension' => 'pdf'],
            default => throw new InvalidArgumentException('Unsupported Commercial report export format.'),
        };
    }

    /** @param array<string, mixed> $snapshot @param array<int, array<string, string>> $insights @return array<string, array<int, array<int, mixed>>> */
    private function sheets(array $snapshot, array $insights): array
    {
        $summaryKey = UiText::get('reports.sections.summary', 'Summary');
        $stagesKey = UiText::get('reports.sections.pipeline_stages', 'Pipeline stages');
        $servicesKey = UiText::get('reports.sections.services', 'Services');
        $productsKey = UiText::get('reports.sections.products', 'Products');
        $bundlesKey = UiText::get('reports.sections.packages', 'Bundles');
        $bundleItemsKey = UiText::get('reports.sections.bundle_items', 'Bundle items');
        $opportunitiesKey = UiText::get('reports.sections.opportunities', 'Opportunities');
        $itemsKey = UiText::get('reports.sections.opportunity_items', 'Opportunity items');
        $topServicesKey = UiText::get('reports.sections.top_services', 'Top services');
        $insightsKey = UiText::get('reports.sections.insights', 'Insights');

        $sheets = [
            $summaryKey => [
                [UiText::get('reports.columns.metric', 'Metric'), UiText::get('reports.columns.value', 'Value')],
                [UiText::get('overview.active_services', 'Active services'), (int) ($snapshot['active_services'] ?? 0)],
                [UiText::get('overview.active_products', 'Active products'), (int) ($snapshot['active_products'] ?? 0)],
                [UiText::get('overview.active_bundles', 'Active bundles'), (int) ($snapshot['active_bundles'] ?? 0)],
                [UiText::get('overview.open_opportunities', 'Open opportunities'), (int) ($snapshot['open_opportunities'] ?? 0)],
                [UiText::get('overview.pipeline_value', 'Pipeline value'), (float) ($snapshot['pipeline_value'] ?? 0)],
                [UiText::get('overview.weighted_pipeline', 'Weighted pipeline'), (float) ($snapshot['weighted_pipeline_value'] ?? 0)],
                [UiText::get('overview.win_rate', 'Win rate').' (%)', (float) ($snapshot['win_rate'] ?? 0)],
                [UiText::get('overview.closing_this_month', 'Closing this month'), (int) ($snapshot['closing_this_month_count'] ?? 0)],
                [UiText::get('reports.metrics.closing_value', 'Closing value this month'), (float) ($snapshot['closing_this_month_value'] ?? 0)],
                [UiText::get('reports.metrics.generated_at', 'Generated at'), now()->format('d/m/Y H:i:s')],
            ],
            $stagesKey => [[
                UiText::get('reports.columns.stage', 'Stage'),
                UiText::get('reports.columns.opportunities', 'Opportunities'),
                UiText::get('reports.columns.share', 'Share (%)'),
                UiText::get('reports.columns.value', 'Value'),
            ]],
            $servicesKey => [[
                UiText::get('reports.columns.service_code', 'Service code'),
                UiText::get('reports.columns.name', 'Name'),
                UiText::get('reports.columns.reference', 'Reference'),
                UiText::get('reports.columns.status', 'Status'),
                UiText::get('reports.columns.products', 'Products'),
                UiText::get('reports.columns.bundles', 'Bundles'),
                UiText::get('reports.columns.updated_at', 'Updated at'),
            ]],
            $productsKey => [[
                UiText::get('reports.columns.product_code', 'Product code'),
                UiText::get('reports.columns.service', 'Service'),
                UiText::get('reports.columns.name', 'Name'),
                UiText::get('reports.columns.audience', 'Audience'),
                UiText::get('reports.columns.unit', 'Unit'),
                UiText::get('reports.columns.default_quantity', 'Default quantity'),
                UiText::get('reports.columns.price_code', 'Price code'),
                UiText::get('reports.columns.billing_period', 'Billing period'),
                UiText::get('reports.columns.price', 'Selling price'),
                UiText::get('reports.columns.renewal_price', 'Renewal price'),
                UiText::get('reports.columns.setup_fee', 'Setup fee'),
                UiText::get('reports.columns.currency', 'Currency'),
                UiText::get('reports.columns.status', 'Status'),
            ]],
            $bundlesKey => [[
                UiText::get('reports.columns.bundle_code', 'Bundle code'),
                UiText::get('reports.columns.primary_service', 'Primary service'),
                UiText::get('reports.columns.name', 'Name'),
                UiText::get('reports.columns.products', 'Products'),
                UiText::get('reports.columns.pricing_type', 'Pricing type'),
                UiText::get('reports.columns.price', 'Effective price'),
                UiText::get('reports.columns.currency', 'Currency'),
                UiText::get('reports.columns.status', 'Status'),
            ]],
            $bundleItemsKey => [[
                UiText::get('reports.columns.bundle_code', 'Bundle code'),
                UiText::get('reports.columns.product_code', 'Product code'),
                UiText::get('reports.columns.service', 'Service'),
                UiText::get('reports.columns.price_code', 'Price code'),
                UiText::get('reports.columns.quantity', 'Quantity'),
                UiText::get('reports.columns.required', 'Required'),
                UiText::get('reports.columns.price_override', 'Price override'),
            ]],
            $opportunitiesKey => [[
                UiText::get('reports.columns.opportunity_code', 'Opportunity code'),
                UiText::get('reports.columns.name', 'Name'),
                UiText::get('reports.columns.customer', 'Company / Contact'),
                UiText::get('reports.columns.line_items', 'Line items'),
                UiText::get('reports.columns.stage', 'Stage'),
                UiText::get('reports.columns.estimated_value', 'Estimated value'),
                UiText::get('reports.columns.currency', 'Currency'),
                UiText::get('reports.columns.probability', 'Probability (%)'),
                UiText::get('reports.columns.weighted_value', 'Weighted value'),
                UiText::get('reports.columns.expected_close', 'Expected close'),
                UiText::get('reports.columns.owner', 'Owner'),
                UiText::get('reports.columns.updated_at', 'Updated at'),
            ]],
            $itemsKey => [[
                UiText::get('reports.columns.opportunity_code', 'Opportunity code'),
                UiText::get('reports.columns.item_type', 'Item type'),
                UiText::get('reports.columns.item_code', 'Item code'),
                UiText::get('reports.columns.price_code', 'Price code'),
                UiText::get('reports.columns.name', 'Name'),
                UiText::get('reports.columns.service', 'Service'),
                UiText::get('reports.columns.quantity', 'Quantity'),
                UiText::get('reports.columns.unit_price', 'Unit price'),
                UiText::get('reports.columns.discount', 'Discount (%)'),
                UiText::get('reports.columns.setup_fee', 'Setup fee'),
                UiText::get('reports.columns.value', 'Line total'),
                UiText::get('reports.columns.currency', 'Currency'),
            ]],
            $topServicesKey => [[
                UiText::get('reports.columns.service', 'Service'),
                UiText::get('reports.columns.count', 'Count'),
                UiText::get('reports.columns.pipeline_value', 'Pipeline value'),
            ]],
            $insightsKey => [[
                UiText::get('reports.columns.level', 'Level'),
                UiText::get('reports.columns.name', 'Title'),
                UiText::get('reports.columns.metric', 'Metric'),
                UiText::get('reports.columns.analysis', 'Analysis'),
            ]],
        ];

        foreach ((array) ($snapshot['stage_breakdown'] ?? []) as $row) {
            $sheets[$stagesKey][] = [$row['label'] ?? '', $row['count'] ?? 0, $row['percentage'] ?? 0, $row['value'] ?? 0];
        }

        Service::query()->withCount(['products', 'bundles'])->orderBy('name')->chunk(250, function ($records) use (&$sheets, $servicesKey): void {
            foreach ($records as $record) {
                $sheets[$servicesKey][] = [
                    $record->service_code,
                    $record->name,
                    $record->reference(),
                    $this->enumValue($record->status),
                    $record->products_count,
                    $record->bundles_count,
                    $record->updated_at?->format('d/m/Y H:i:s'),
                ];
            }
        });

        Product::query()->with(['service:id,name', 'prices'])->orderBy('name')->chunk(150, function ($records) use (&$sheets, $productsKey): void {
            foreach ($records as $record) {
                $prices = $record->prices->isNotEmpty() ? $record->prices : collect([null]);
                foreach ($prices as $price) {
                    $billingPeriod = $price ? trim(implode(' ', array_filter([
                        $price->billing_period,
                        $this->enumValue($price->billing_period_unit),
                    ], static fn ($value): bool => filled($value)))) : '';

                    $sheets[$productsKey][] = [
                        $record->product_code,
                        $record->service?->name,
                        $record->name,
                        $this->enumValue($record->audience_type),
                        $record->unit,
                        (float) $record->default_quantity,
                        $price?->price_code,
                        $billingPeriod,
                        $price?->price !== null ? (float) $price->price : null,
                        $price?->renewal_price !== null ? (float) $price->renewal_price : null,
                        $price?->setup_fee !== null ? (float) $price->setup_fee : null,
                        $price?->currency,
                        $this->enumValue($record->status),
                    ];
                }
            }
        });

        Bundle::query()->with(['primaryService:id,name', 'items.product.prices', 'items.selectedPrice'])->withCount('items')->orderBy('name')->chunk(150, function ($records) use (&$sheets, $bundlesKey): void {
            foreach ($records as $record) {
                $sheets[$bundlesKey][] = [
                    $record->bundle_code,
                    $record->primaryService?->name,
                    $record->name,
                    $record->items_count,
                    $this->enumValue($record->pricing_type),
                    $record->effectivePrice(),
                    $record->currency,
                    $this->enumValue($record->status),
                ];
            }
        });

        BundleItem::query()
            ->with(['bundle:id,bundle_code', 'product:id,service_id,product_code', 'product.service:id,name', 'selectedPrice:id,price_code'])
            ->orderBy('bundle_id')
            ->orderBy('sort_order')
            ->chunk(300, function ($records) use (&$sheets, $bundleItemsKey): void {
                foreach ($records as $record) {
                    $sheets[$bundleItemsKey][] = [
                        $record->bundle?->bundle_code,
                        $record->product?->product_code,
                        $record->product?->service?->name,
                        $record->selectedPrice?->price_code,
                        (float) $record->quantity,
                        $record->required ? 1 : 0,
                        $record->price_override !== null ? (float) $record->price_override : null,
                    ];
                }
            });

        Opportunity::query()->withCount('items')->latest('updated_at')->chunk(250, function ($records) use (&$sheets, $opportunitiesKey): void {
            foreach ($records as $record) {
                $value = (float) ($record->estimated_value ?? 0);
                $probability = (int) ($record->probability ?? 0);
                $stage = $record->stage instanceof OpportunityStage
                    ? $record->stage->label()
                    : (OpportunityStage::tryFrom((string) $record->stage)?->label() ?? (string) $record->stage);

                $sheets[$opportunitiesKey][] = [
                    $record->opportunity_code,
                    $record->title,
                    $record->company_name_snapshot ?: $record->contact_name_snapshot,
                    $record->items_count,
                    $stage,
                    $value,
                    $record->currency ?: 'VND',
                    $probability,
                    round($value * ($probability / 100), 2),
                    $record->expected_close_date?->format('d/m/Y'),
                    $record->assigned_employee_name_snapshot,
                    $record->updated_at?->format('d/m/Y H:i:s'),
                ];
            }
        });

        OpportunityItem::query()->with(['opportunity:id,opportunity_code', 'selectedPrice:id,price_code'])->orderBy('opportunity_id')->orderBy('sort_order')->chunk(300, function ($records) use (&$sheets, $itemsKey): void {
            foreach ($records as $record) {
                $sheets[$itemsKey][] = [
                    $record->opportunity?->opportunity_code,
                    $this->enumValue($record->item_type),
                    $record->item_code_snapshot,
                    $record->selectedPrice?->price_code,
                    $record->item_name_snapshot,
                    $record->service_name_snapshot,
                    (float) $record->quantity,
                    $record->unit_price !== null ? (float) $record->unit_price : null,
                    (float) $record->discount_percent,
                    (float) $record->setup_fee,
                    (float) $record->total,
                    $record->currency,
                ];
            }
        });

        foreach ((array) ($snapshot['top_services'] ?? []) as $row) {
            $sheets[$topServicesKey][] = [$row['name'] ?? '', $row['count'] ?? 0, $row['value'] ?? 0];
        }
        foreach ($insights as $row) {
            $sheets[$insightsKey][] = [$this->insightLevelLabel((string) ($row['level'] ?? 'neutral')), $row['title'] ?? '', $row['metric'] ?? '', $row['body'] ?? ''];
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
            UiText::get('overview.active_products', 'Active products').': '.(int) ($snapshot['active_products'] ?? 0),
            UiText::get('overview.active_bundles', 'Active bundles').': '.(int) ($snapshot['active_bundles'] ?? 0),
            UiText::get('overview.open_opportunities', 'Open opportunities').': '.(int) ($snapshot['open_opportunities'] ?? 0),
            UiText::get('overview.pipeline_value', 'Pipeline value').': '.number_format((float) ($snapshot['pipeline_value'] ?? 0), 0, '.', ',').' VND',
            UiText::get('overview.weighted_pipeline', 'Weighted pipeline').': '.number_format((float) ($snapshot['weighted_pipeline_value'] ?? 0), 0, '.', ',').' VND',
            UiText::get('overview.win_rate', 'Win rate').': '.number_format((float) ($snapshot['win_rate'] ?? 0), 1).'%',
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
