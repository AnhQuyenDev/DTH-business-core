<?php
namespace App\Filament\Resources\Security\RbacRoleResource\Pages;
use App\Filament\Resources\Security\RbacRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditRbacRole extends EditRecord { protected static string $resource = RbacRoleResource::class; protected function getHeaderActions(): array { return [DeleteAction::make()->visible(fn (): bool => ! $this->record->is_system)]; } }
