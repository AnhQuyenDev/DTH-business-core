<?php

namespace Dth\Email\Filament\Resources\EmailTemplateResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-plus'),
        ];
    }
}
