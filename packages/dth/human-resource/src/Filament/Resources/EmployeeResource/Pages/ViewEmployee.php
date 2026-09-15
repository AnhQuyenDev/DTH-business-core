<?php

namespace Dth\HumanResource\Filament\Resources\EmployeeResource\Pages;

use Dth\HumanResource\Filament\Resources\EmployeeResource;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->label(UiText::get('common.actions.edit', 'Edit'))];
    }
}
