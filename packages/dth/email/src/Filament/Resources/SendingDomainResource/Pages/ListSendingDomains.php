<?php

namespace Dth\Email\Filament\Resources\SendingDomainResource\Pages;

use Dth\Email\Filament\Resources\SendingDomainResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListSendingDomains extends ListRecords
{
    protected static string $resource = SendingDomainResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(UiText::get('domain.list.title', 'Hạ tầng gửi Email'), 'domain', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('domain.list.subheading', 'Quản lý tên miền gửi, theo dõi xác thực DNS và duy trì độ tin cậy hạ tầng email.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(UiText::get('common.actions.new', 'Thêm tên miền'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
