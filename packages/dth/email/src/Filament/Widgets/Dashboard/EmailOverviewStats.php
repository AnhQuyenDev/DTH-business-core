<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Filament\Resources\EmailDeliveryLogResource;
use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Filament\Widgets\Dashboard\Concerns\FormatsDashboardMetrics;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\Widget;

class EmailOverviewStats extends Widget
{
    use FormatsDashboardMetrics;
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected string $view = 'dth-email::filament.widgets.dashboard.email-overview-stats';
    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $service = app(EmailAnalyticsService::class);
        $filters = $this->analyticsFilters();
        $overview = $service->overview($filters);
        $trend = $service->trend($filters);
        $snapshot = $overview->current;

        $sentTrend = array_map(static fn ($point): int => $point->sent, $trend);
        $openTrend = array_map(static fn ($point): int => $point->uniqueOpened, $trend);
        $clickTrend = array_map(static fn ($point): int => $point->uniqueClicked, $trend);
        $unsubscribeTrend = array_map(static fn ($point): int => $point->unsubscribed, $trend);
        $failureTrend = array_map(static fn ($point): int => $point->failed, $trend);

        return [
            'stats' => [
                $this->stat(
                    label: UiText::get('dashboard.metrics.campaigns', 'Campaign'),
                    value: $this->number($snapshot->campaigns),
                    icon: 'heroicon-o-paper-airplane',
                    tone: 'amber',
                    meta: $this->deltaMeta($overview->deltas['campaigns'] ?? null),
                    url: EmailCampaignResource::getUrl('index'),
                ),
                $this->stat(
                    label: UiText::get('dashboard.metrics.recipients', 'Recipients'),
                    value: $this->number($snapshot->recipients),
                    icon: 'heroicon-o-users',
                    tone: 'blue',
                    meta: $this->deltaMeta($overview->deltas['recipients'] ?? null),
                    url: EmailCampaignResource::getUrl('index'),
                ),
                $this->stat(
                    label: UiText::get('dashboard.metrics.sent', 'Sent'),
                    value: $this->number($snapshot->sent),
                    icon: 'heroicon-o-envelope',
                    tone: 'green',
                    meta: $this->deltaMeta($overview->deltas['sent'] ?? null),
                    sparkline: $this->sparkline($sentTrend),
                    url: EmailDeliveryLogResource::getUrl('index'),
                ),
                $this->stat(
                    label: UiText::get('dashboard.metrics.open_rate', 'Open rate'),
                    value: $this->percent($snapshot->openRate),
                    icon: 'heroicon-o-eye',
                    tone: 'blue',
                    meta: $this->deltaMeta($overview->deltas['open_rate'] ?? null),
                    sparkline: $this->sparkline($openTrend),
                ),
                $this->stat(
                    label: UiText::get('dashboard.metrics.click_rate', 'Click rate'),
                    value: $this->percent($snapshot->clickRate),
                    icon: 'heroicon-o-cursor-arrow-rays',
                    tone: 'green',
                    meta: $this->deltaMeta($overview->deltas['click_rate'] ?? null),
                    sparkline: $this->sparkline($clickTrend),
                ),
                $this->stat(
                    label: UiText::get('dashboard.metrics.ctor', 'Click-to-open rate'),
                    value: $this->percent($snapshot->clickToOpenRate),
                    icon: 'heroicon-o-link',
                    tone: 'blue',
                    meta: $this->deltaMeta($overview->deltas['click_to_open_rate'] ?? null),
                    sparkline: $this->sparkline($clickTrend),
                ),
                $this->stat(
                    label: UiText::get('dashboard.metrics.unsubscribe_rate', 'Unsubscribe rate'),
                    value: $this->percent($snapshot->unsubscribeRate),
                    icon: 'heroicon-o-user-minus',
                    tone: $snapshot->unsubscribeRate > 1 ? 'red' : 'green',
                    meta: $this->deltaMeta($overview->deltas['unsubscribe_rate'] ?? null, lowerIsBetter: true),
                    sparkline: $this->sparkline($unsubscribeTrend),
                ),
                $this->stat(
                    label: UiText::get('dashboard.metrics.failure_rate', 'Failure rate'),
                    value: $this->percent($snapshot->failureRate),
                    icon: 'heroicon-o-exclamation-triangle',
                    tone: $snapshot->failureRate > 0 ? 'red' : 'green',
                    meta: $this->deltaMeta($overview->deltas['failure_rate'] ?? null, lowerIsBetter: true),
                    sparkline: $this->sparkline($failureTrend),
                ),
            ],
        ];
    }


    protected function number(int|float $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    protected function percent(?float $value): string
    {
        return $value === null ? 'N/A' : number_format($value, 1, ',', '.').'%';
    }

    /**
     * @param array{description: string, color: string, icon: string} $meta
     * @return array<string, mixed>
     */
    private function stat(
        string $label,
        string $value,
        string $icon,
        string $tone,
        array $meta,
        ?string $sparkline = null,
        ?string $url = null,
    ): array {
        return compact('label', 'value', 'icon', 'tone', 'meta', 'sparkline', 'url');
    }

    /** @param array<int, int|float> $values */
    private function sparkline(array $values): ?string
    {
        $values = array_values($values);

        if (count($values) < 2) {
            return null;
        }

        $width = 92.0;
        $height = 30.0;
        $min = (float) min($values);
        $max = (float) max($values);
        $range = max(1.0, $max - $min);
        $last = max(1, count($values) - 1);

        return implode(' ', array_map(
            static function (int|float $value, int $index) use ($width, $height, $min, $range, $last): string {
                $x = ($index / $last) * $width;
                $y = $height - ((((float) $value - $min) / $range) * ($height - 4.0)) - 2.0;

                return number_format($x, 1, '.', '').','.number_format($y, 1, '.', '');
            },
            $values,
            array_keys($values),
        ));
    }
}
