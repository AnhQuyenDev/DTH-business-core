<?php

namespace Dth\Email\Filament\Resources\EmailTemplateResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(UiText::get('template.create.title', 'Tạo mẫu Email'), 'template', 'violet');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('template.create.subheading', 'Soạn thảo nội dung, gắn biến cá nhân hóa và quản lý trạng thái của mẫu email.');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(UiText::get('common.actions.save', 'Lưu'))
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Hủy'))
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
