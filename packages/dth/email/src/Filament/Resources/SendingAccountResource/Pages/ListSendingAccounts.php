<?php

namespace Dth\Email\Filament\Resources\SendingAccountResource\Pages;

use Dth\Email\Filament\Resources\SendingAccountResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListSendingAccounts extends ListRecords
{
    protected static string $resource = SendingAccountResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(UiText::get('account.list.title', 'Tài khoản gửi'), 'account', 'green');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('account.list.subheading', 'Quản lý SMTP, kiểm tra trạng thái hoạt động và giám sát sức khỏe các tài khoản gửi email.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(UiText::get('common.actions.new', 'Tạo tài khoản gửi'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
