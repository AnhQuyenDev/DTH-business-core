<?php

namespace Dth\Commercial\Filament\Resources\OpportunityResource\Pages;

use Dth\Commercial\Filament\Resources\OpportunityResource;
use Dth\Commercial\Filament\Widgets\OpportunityListStats;
use Dth\Commercial\Filament\Widgets\OpportunityPipelineBoard;
use Dth\Commercial\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    public function getTitle(): string|Htmlable
    {
        return UiText::get('pages.opportunities.title', 'Business opportunities');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.opportunities.subheading', 'Track pipeline movement, expected value, close dates and ownership from qualification through outcome.');
    }

    /** @return array<class-string<\Filament\Widgets\Widget>> */
    protected function getHeaderWidgets(): array
    {
        return [
            OpportunityListStats::class,
            OpportunityPipelineBoard::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('actions.create_opportunity', 'Create opportunity'))
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-entry-action dth-com-entry-action--teal']),
        ];
    }
}
