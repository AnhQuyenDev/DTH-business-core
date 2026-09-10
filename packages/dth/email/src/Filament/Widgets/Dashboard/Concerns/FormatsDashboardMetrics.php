<?php

namespace Dth\Email\Filament\Widgets\Dashboard\Concerns;

use Dth\Email\DTO\AnalyticsMetricDelta;
use Dth\Email\Support\UiText;

trait FormatsDashboardMetrics
{
    protected function number(int|float $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }

    protected function percent(?float $value): string
    {
        return $value === null ? 'N/A' : number_format($value, 1).'%';
    }

    /** @return array{description: string, color: string, icon: string} */
    protected function deltaMeta(?AnalyticsMetricDelta $delta, bool $lowerIsBetter = false): array
    {
        if ($delta === null || $delta->direction === 'unavailable') {
            return [
                'description' => UiText::get('dashboard.delta.unavailable', 'No comparison available'),
                'color' => 'gray',
                'icon' => 'heroicon-m-minus',
            ];
        }

        $change = $delta->percentChange !== null
            ? (($delta->percentChange > 0 ? '+' : '').number_format($delta->percentChange, 1).'%')
            : (($delta->absoluteChange ?? 0) > 0 ? '+' : '').number_format((float) ($delta->absoluteChange ?? 0), 1);

        if ($delta->direction === 'flat') {
            return [
                'description' => UiText::get(
                    'dashboard.delta.compared_previous',
                    ':change vs previous period',
                    ['change' => $change],
                ),
                'color' => 'gray',
                'icon' => 'heroicon-m-minus',
            ];
        }

        $improving = $lowerIsBetter
            ? $delta->direction === 'down'
            : $delta->direction === 'up';

        return [
            'description' => UiText::get(
                'dashboard.delta.compared_previous',
                ':change vs previous period',
                ['change' => $change],
            ),
            'color' => $improving ? 'success' : 'danger',
            'icon' => $delta->direction === 'up'
                ? 'heroicon-m-arrow-trending-up'
                : 'heroicon-m-arrow-trending-down',
        ];
    }
}
