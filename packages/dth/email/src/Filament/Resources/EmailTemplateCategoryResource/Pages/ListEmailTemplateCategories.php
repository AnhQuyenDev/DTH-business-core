<?php

namespace Dth\Email\Filament\Resources\EmailTemplateCategoryResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplateCategories extends ListRecords
{
    protected static string $resource = EmailTemplateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-plus'),
        ];
    }
}
