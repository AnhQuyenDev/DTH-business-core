<?php

namespace App\Filament\Resources\EmailTemplateCategoryResource\Pages;

use App\Filament\Resources\EmailTemplateCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplateCategory extends EditRecord
{
    protected static string $resource = EmailTemplateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
