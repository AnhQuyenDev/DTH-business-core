<?php

namespace Dth\Crm\Filament\Resources\StaffResource\Pages;

use Dth\Crm\Filament\Resources\StaffResource;
use Dth\Crm\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffs extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('common.actions.add', 'Add'))
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--violet']),
        ];
    }
}
