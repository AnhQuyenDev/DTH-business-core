<?php

namespace Dth\Marketing\Filament\Resources\LandingPageResource\Pages;

use Dth\Marketing\Filament\Resources\LandingPageResource;
use Dth\Marketing\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLandingPages extends ListRecords
{
    protected static string $resource = LandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('common.actions.new', 'New'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
