<?php
namespace Dth\AccountManagement\Filament\Resources\RoleResource\Pages;
use Dth\AccountManagement\Filament\Resources\RoleResource;
use Dth\AccountManagement\Filament\Support\AccountPageUi;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
class ListRoles extends ListRecords { protected static string $resource = RoleResource::class; public function getTitle(): string|Htmlable { return AccountPageUi::title(UiText::get('navigation.roles','Vai trò & Phân quyền'),'role'); } protected function getHeaderActions(): array { return [CreateAction::make()->label(UiText::get('actions.new_role','Tạo vai trò'))->icon('heroicon-o-plus-circle')->color('gray')->extraAttributes(['class'=>'dth-acc-entry-action'])->visible(fn (): bool => app(AccountAuthorization::class)->allows('accounts.roles.manage'))]; } }
