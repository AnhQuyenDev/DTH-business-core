<?php

namespace Dth\HumanResource\Filament\Resources\EmployeeResource\Pages;

use Dth\HumanResource\Filament\Resources\EmployeeResource;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(UiText::get('actions.new_employee', 'New employee'))];
    }
}
