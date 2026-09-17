<?php

namespace Dth\Commercial\Filament\Widgets;

use Dth\Commercial\Models\Opportunity;
use Dth\Commercial\Support\UiText;
use Filament\Widgets\Widget;

final class OpportunityListStats extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'dth-commercial::filament.widgets.commercial-list-stats';

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $openStages = ['discovery', 'qualified', 'proposal', 'negotiation'];
        $open = Opportunity::query()->whereIn('stage', $openStages)->get(['estimated_value', 'probability']);
        $won = Opportunity::query()->where('stage', 'won')->count();
        $lost = Opportunity::query()->where('stage', 'lost')->count();
        $decided = $won + $lost;
        $winRate = $decided > 0 ? round(($won / $decided) * 100) : 0;

        $closing = Opportunity::query()
            ->whereIn('stage', $openStages)
            ->whereYear('expected_close_date', now()->year)
            ->whereMonth('expected_close_date', now()->month)
            ->get(['estimated_value']);

        $weighted = $open->sum(static fn (Opportunity $opportunity): float =>
            ((float) ($opportunity->estimated_value ?? 0)) * ((int) $opportunity->probability / 100)
        );

        return [
            'stats' => [
                [
                    'label' => UiText::get('stats.open_opportunities', 'Open opportunities'),
                    'value' => number_format($open->count(), 0, ',', '.'),
                    'meta' => UiText::get('stats.pipeline_in_progress', 'Pipeline in progress'),
                    'icon' => 'heroicon-o-briefcase',
                    'tone' => 'teal',
                ],
                [
                    'label' => UiText::get('stats.weighted_pipeline', 'Weighted pipeline'),
                    'value' => number_format($weighted, 0, ',', '.').' ₫',
                    'meta' => UiText::get('stats.probability_adjusted', 'Adjusted by probability'),
                    'icon' => 'heroicon-o-banknotes',
                    'tone' => 'blue',
                ],
                [
                    'label' => UiText::get('stats.win_rate', 'Win rate'),
                    'value' => $winRate.'%',
                    'meta' => UiText::get('stats.closed_outcomes', ':count closed outcomes', ['count' => number_format($decided, 0, ',', '.')]),
                    'icon' => 'heroicon-o-trophy',
                    'tone' => 'green',
                ],
                [
                    'label' => UiText::get('stats.closing_this_month', 'Closing this month'),
                    'value' => number_format($closing->count(), 0, ',', '.'),
                    'meta' => number_format((float) $closing->sum('estimated_value'), 0, ',', '.').' ₫',
                    'icon' => 'heroicon-o-calendar-days',
                    'tone' => 'amber',
                ],
            ],
        ];
    }
}
