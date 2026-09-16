<?php

namespace Dth\Crm\Filament\Resources\PersonalContactResource\Pages;

use Dth\Crm\Filament\Resources\PersonalContactResource;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPersonalContacts extends ListRecords
{
    protected static string $resource = PersonalContactResource::class;

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('navigation.personal_contacts', 'Personal contacts'), 'contact');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('common.actions.add', 'Add'))
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--teal']),
        ];
    }
}
