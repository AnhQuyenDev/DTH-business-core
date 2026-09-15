<?php

namespace Dth\Email\Filament\Resources\SendingDomainResource\Pages;

use Dth\Email\Filament\Resources\SendingDomainResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditSendingDomain extends EditRecord
{
    protected static string $resource = SendingDomainResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(UiText::get('domain.edit.title', 'Tạo / Chỉnh sửa Tên miền gửi'), 'domain', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('domain.edit.subheading', 'Cập nhật thông tin tên miền, ghi chú triển khai và trạng thái xác thực bản ghi DNS.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(UiText::get('common.actions.delete', 'Delete'))
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(UiText::get('common.actions.save', 'Save'))
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
