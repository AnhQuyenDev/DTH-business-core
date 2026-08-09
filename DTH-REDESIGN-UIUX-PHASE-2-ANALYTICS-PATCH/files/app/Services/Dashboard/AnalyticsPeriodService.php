<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;

final class AnalyticsPeriodService
{
    /**
     * @return array{start: Carbon, end: Carbon, previous_start: Carbon, previous_end: Carbon, days: int}
     */
    public function resolve(string $period): array
    {
        $end = now()->endOfDay();

        $start = match ($period) {
            '7d' => now()->subDays(6)->startOfDay(),
            '90d' => now()->subDays(89)->startOfDay(),
            'ytd' => now()->startOfYear(),
            default => now()->subDays(29)->startOfDay(),
        };

        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return [
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
            'days' => $days,
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, start: Carbon, end: Carbon}>
     */
    public function buckets(Carbon $start, Carbon $end, int $preferred = 8): array
    {
        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $bucketDays = max(1, (int) ceil($days / max(1, $preferred)));
        $rows = [];
        $cursor = $start->copy()->startOfDay();
        $index = 0;

        while ($cursor->lte($end)) {
            $bucketStart = $cursor->copy();
            $bucketEnd = $cursor->copy()->addDays($bucketDays - 1)->endOfDay();
            if ($bucketEnd->gt($end)) {
                $bucketEnd = $end->copy();
            }

            $rows[] = [
                'key' => 'bucket_'.$index,
                'label' => $bucketDays === 1
                    ? $bucketStart->format('d/m')
                    : $bucketStart->format('d/m').'–'.$bucketEnd->format('d/m'),
                'start' => $bucketStart,
                'end' => $bucketEnd,
            ];

            $cursor = $bucketEnd->copy()->addSecond()->startOfDay();
            $index++;
        }

        return $rows;
    }

    /**
     * @return array{percent: ?float, direction: string, text: string}
     */
    public function delta(float|int $current, float|int $previous): array
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if (abs($previous) < 0.00001) {
            if (abs($current) < 0.00001) {
                return ['percent' => 0.0, 'direction' => 'flat', 'text' => '0%'];
            }

            return ['percent' => null, 'direction' => 'up', 'text' => __('analytics.new_in_period')];
        }

        $percent = round((($current - $previous) / abs($previous)) * 100, 1);

        return [
            'percent' => $percent,
            'direction' => $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat'),
            'text' => ($percent > 0 ? '+' : '').number_format($percent, 1).'% ',
        ];
    }
}
