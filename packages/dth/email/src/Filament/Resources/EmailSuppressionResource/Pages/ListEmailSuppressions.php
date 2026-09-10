<?php

namespace Dth\Email\Filament\Resources\EmailSuppressionResource\Pages;

use Dth\Email\Filament\Resources\EmailSuppressionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmailSuppressions extends ListRecords
{
    protected static string $resource =
        EmailSuppressionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add')
                ->icon('heroicon-o-plus'),
        ];
    }
}