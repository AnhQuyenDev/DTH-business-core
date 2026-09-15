<?php
namespace Dth\HumanResource\Filament\Resources\DepartmentResource\Pages;
use Dth\HumanResource\Filament\Resources\DepartmentResource;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListDepartments extends ListRecords { protected static string $resource = DepartmentResource::class; protected function getHeaderActions(): array { return [CreateAction::make()->label(UiText::get('actions.new_department','New department'))]; } }
