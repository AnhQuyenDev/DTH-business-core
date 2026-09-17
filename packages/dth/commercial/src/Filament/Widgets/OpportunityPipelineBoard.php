<?php

namespace Dth\Commercial\Filament\Widgets;

use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Models\Opportunity;
use Dth\Commercial\Support\UiText;
use Filament\Widgets\Widget;

final class OpportunityPipelineBoard extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'dth-commercial::filament.widgets.opportunity-pipeline-board';

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $stages = [
            OpportunityStage::Discovery,
            OpportunityStage::Qualified,
            OpportunityStage::Proposal,
            OpportunityStage::Negotiation,
            OpportunityStage::Won,
            OpportunityStage::Lost,
        ];

        $tones = [
            OpportunityStage::Discovery->value => 'sky',
            OpportunityStage::Qualified->value => 'cyan',
            OpportunityStage::Proposal->value => 'amber',
            OpportunityStage::Negotiation->value => 'orange',
            OpportunityStage::Won->value => 'green',
            OpportunityStage::Lost->value => 'rose',
        ];

        $recordsByStage = Opportunity::query()
            ->whereIn('stage', array_map(static fn (OpportunityStage $stage): string => $stage->value, $stages))
            ->latest('updated_at')
            ->get()
            ->groupBy(static fn (Opportunity $opportunity): string =>
                $opportunity->stage instanceof OpportunityStage
                    ? $opportunity->stage->value
                    : (string) $opportunity->stage
            );

        $columns = [];

        foreach ($stages as $stage) {
            $records = $recordsByStage->get($stage->value, collect());

            $columns[] = [
                'stage' => $stage,
                'label' => $stage->label(),
                'tone' => $tones[$stage->value] ?? 'slate',
                'count' => $records->count(),
                'value' => (float) $records->sum('estimated_value'),
                'records' => $records->take(4),
            ];
        }

        return [
            'title' => UiText::get('pipeline.title', 'Opportunity pipeline'),
            'subheading' => UiText::get('pipeline.subheading', 'See where every opportunity is and focus on the deals that need attention.'),
            'columns' => $columns,
        ];
    }
}
