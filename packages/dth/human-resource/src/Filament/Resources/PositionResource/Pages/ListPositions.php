<?php

namespace Dth\HumanResource\Filament\Resources\PositionResource\Pages;

use Dth\HumanResource\Filament\Resources\PositionResource;
use Dth\HumanResource\Filament\Support\HumanResourceDataActions;
use Dth\HumanResource\Filament\Support\HumanResourcePageUi;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPositions extends ListRecords
{
    protected static string $resource = PositionResource::class;

    public function getTitle(): string|Htmlable
    {
        return HumanResourcePageUi::title(
            UiText::get('pages.positions.title', 'Job titles'),
            'position',
            'amber',
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('actions.new_position', 'New job title'))
                ->icon('heroicon-o-plus-circle')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-hr-entry-action dth-hr-entry-action--amber']),
            ...HumanResourceDataActions::make(
                'positions',
                fn () => $this->getFilteredTableQuery(),
                'hr-job-titles',
                'Job titles',
            ),
        ];
    }
}
