<?php
namespace Dth\AccountManagement\Filament\Resources\AccountUserResource\Pages;

use Dth\AccountManagement\Filament\Resources\AccountUserResource;
use Dth\AccountManagement\Filament\Support\AccountPageUi;
use Dth\AccountManagement\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListAccountUsers extends ListRecords
{
    protected static string $resource = AccountUserResource::class;
    public function getTitle(): string|Htmlable { return AccountPageUi::title(UiText::get('navigation.users', 'Người dùng'), 'user'); }
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(UiText::get('actions.new_user', 'Tạo tài khoản'))->icon('heroicon-o-user-plus')->color('gray')->extraAttributes(['class' => 'dth-acc-entry-action'])];
    }
}
