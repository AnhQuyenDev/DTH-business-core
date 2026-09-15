<?php

namespace Dth\HumanResource\Filament\Resources\EmployeeResource\Pages;

use Dth\HumanResource\Filament\Resources\EmployeeResource;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete'))];
    }
}
