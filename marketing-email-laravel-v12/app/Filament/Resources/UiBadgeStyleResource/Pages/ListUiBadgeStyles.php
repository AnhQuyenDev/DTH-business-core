<?php

namespace App\Filament\Resources\UiBadgeStyleResource\Pages;

use App\Filament\Resources\UiBadgeStyleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUiBadgeStyles extends ListRecords
{
    protected static string $resource = UiBadgeStyleResource::class;

    public function getSubheading(): ?string
    {
        return __('configuration.appearance.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('configuration.appearance.create'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
