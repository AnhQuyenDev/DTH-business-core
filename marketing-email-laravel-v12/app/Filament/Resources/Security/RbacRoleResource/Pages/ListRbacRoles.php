<?php
namespace App\Filament\Resources\Security\RbacRoleResource\Pages;
use App\Filament\Resources\Security\RbacRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListRbacRoles extends ListRecords { protected static string $resource = RbacRoleResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }
