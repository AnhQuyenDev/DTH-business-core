<?php
namespace Dth\AccountManagement\Filament\Resources\AccountUserResource\Pages;

use Dth\AccountManagement\Filament\Resources\AccountUserResource;
use Dth\AccountManagement\Filament\Support\AccountPageUi;
use Dth\AccountManagement\Filament\Support\AccountUserDataActions;
use Dth\AccountManagement\Services\EmployeeLinkService;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\UiText;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListAccountUsers extends ListRecords
{
    protected static string $resource = AccountUserResource::class;
    public function getTitle(): string|Htmlable { return AccountPageUi::title(UiText::get('navigation.users', 'Người dùng'), 'user'); }
    protected function getHeaderActions(): array
    {
        return [
            Action::make('hrAccountRequests')
                ->label(fn (): string => strtr(UiText::get('hr_requests.action', 'Yêu cầu từ Nhân sự (:count)'), [':count' => count(app(EmployeeLinkService::class)->pendingHrRequests())]))
                ->icon('heroicon-o-inbox-stack')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-acc-entry-action'])
                ->modalHeading(UiText::get('hr_requests.title', 'Yêu cầu tài khoản từ Nhân sự'))
                ->modalDescription(UiText::get('hr_requests.description', 'Các yêu cầu cấp tài khoản mới hoặc đồng bộ danh tính tài khoản do HR gửi sang quản trị tài khoản.'))
                ->modalWidth('4xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(UiText::get('common.actions.close', 'Đóng'))
                ->modalContent(fn () => view('dth-account-management::filament.modals.hr-account-requests', [
                    'requests' => app(EmployeeLinkService::class)->pendingHrRequests(),
                ]))
                ->visible(fn (): bool => app(AccountAuthorization::class)->allows('accounts.users.manage')
                    && app(EmployeeLinkService::class)->available()
                    && app(EmployeeLinkService::class)->pendingHrRequests() !== []),
            AccountUserDataActions::import(),
            CreateAction::make()
                ->label(UiText::get('actions.new_user', 'Tạo tài khoản'))
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-acc-entry-action'])
                ->visible(fn (): bool => app(AccountAuthorization::class)->allows('accounts.users.manage')),
        ];
    }
}
