<?php

namespace Dth\Email\Filament\Resources\EmailTemplateResource\Widgets;

use Carbon\CarbonImmutable;
use Dth\Email\Enums\EmailTemplateStatus;
use Dth\Email\Models\EmailTemplate;
use Dth\Email\Support\UiText;
use Filament\Widgets\Widget;

class TemplateListStats extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'dth-email::filament.widgets.template-list-stats';

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $counts = EmailTemplate::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(static fn ($value): int => (int) $value)
            ->all();

        $today = CarbonImmutable::today();
        $currentStart = $today->subDays(29)->startOfDay();
        $previousStart = $today->subDays(59)->startOfDay();
        $previousEnd = $currentStart->subSecond();

        $periodTemplates = EmailTemplate::query()
            ->whereBetween('created_at', [$previousStart, $today->endOfDay()])
            ->get(['created_at', 'status']);

        $definitions = [
            [
                'key' => 'total',
                'label' => UiText::get('template.list.stats.total', 'Tổng template'),
                'value' => array_sum($counts),
                'icon' => 'heroicon-o-document-text',
                'tone' => 'amber',
                'status' => null,
            ],
            [
                'key' => EmailTemplateStatus::Draft->value,
                'label' => UiText::get('template.status.draft', 'Bản nháp'),
                'value' => $counts[EmailTemplateStatus::Draft->value] ?? 0,
                'icon' => 'heroicon-o-pencil-square',
                'tone' => 'blue',
                'status' => EmailTemplateStatus::Draft,
            ],
            [
                'key' => EmailTemplateStatus::Active->value,
                'label' => UiText::get('template.status.active', 'Hoạt động'),
                'value' => $counts[EmailTemplateStatus::Active->value] ?? 0,
                'icon' => 'heroicon-o-check-circle',
                'tone' => 'green',
                'status' => EmailTemplateStatus::Active,
            ],
            [
                'key' => EmailTemplateStatus::Inactive->value,
                'label' => UiText::get('template.status.inactive', 'Không hoạt động'),
                'value' => $counts[EmailTemplateStatus::Inactive->value] ?? 0,
                'icon' => 'heroicon-o-archive-box',
                'tone' => 'violet',
                'status' => EmailTemplateStatus::Inactive,
            ],
        ];

        $stats = [];

        foreach ($definitions as $definition) {
            $status = $definition['status'];

            $current = $periodTemplates->filter(function (EmailTemplate $template) use ($currentStart, $today, $status): bool {
                if ($template->created_at === null || $template->created_at->lt($currentStart) || $template->created_at->gt($today->endOfDay())) {
                    return false;
                }

                return $status === null || $template->status === $status;
            })->count();

            $previous = $periodTemplates->filter(function (EmailTemplate $template) use ($previousStart, $previousEnd, $status): bool {
                if ($template->created_at === null || $template->created_at->lt($previousStart) || $template->created_at->gt($previousEnd)) {
                    return false;
                }

                return $status === null || $template->status === $status;
            })->count();

            $delta = $this->percentageDelta($current, $previous);
            $daily = [];

            for ($offset = 7; $offset >= 0; $offset--) {
                $date = $today->subDays($offset);
                $daily[] = $periodTemplates->filter(function (EmailTemplate $template) use ($date, $status): bool {
                    return $template->created_at?->isSameDay($date)
                        && ($status === null || $template->status === $status);
                })->count();
            }

            $stats[] = [
                ...$definition,
                'formattedValue' => number_format((int) $definition['value'], 0, ',', '.'),
                'delta' => $delta,
                'deltaLabel' => ($delta > 0 ? '+' : '').number_format($delta, 0, ',', '.').'%',
                'sparkline' => $this->sparkline($daily),
            ];
        }

        return ['stats' => $stats];
    }

    private function percentageDelta(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /** @param array<int, int|float> $values */
    private function sparkline(array $values): string
    {
        $width = 88.0;
        $height = 28.0;
        $min = (float) min($values ?: [0]);
        $max = (float) max($values ?: [0]);
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