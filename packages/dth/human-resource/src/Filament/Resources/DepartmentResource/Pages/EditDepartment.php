<?php
namespace Dth\HumanResource\Filament\Resources\DepartmentResource\Pages;
use Dth\HumanResource\Filament\Resources\DepartmentResource;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditDepartment extends EditRecord { protected static string $resource = DepartmentResource::class; protected function getHeaderActions(): array { return [DeleteAction::make()->label(UiText::get('common.actions.delete','Delete'))]; } }
