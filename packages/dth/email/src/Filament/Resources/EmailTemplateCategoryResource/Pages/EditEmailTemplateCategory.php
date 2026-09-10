<?php

namespace Dth\Email\Filament\Resources\EmailTemplateCategoryResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplateCategory extends EditRecord
{
    protected static string $resource = EmailTemplateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Delete')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Save')
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label('Cancel')
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
