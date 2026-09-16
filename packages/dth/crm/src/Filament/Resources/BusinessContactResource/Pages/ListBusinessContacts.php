<?php

namespace Dth\Crm\Filament\Resources\BusinessContactResource\Pages;

use Dth\Crm\Filament\Resources\BusinessContactResource;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListBusinessContacts extends ListRecords
{
    protected static string $resource = BusinessContactResource::class;

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('navigation.business_contacts', 'Business contacts'), 'company', 'blue');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('common.actions.add', 'Add'))
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--blue']),
        ];
    }
}
