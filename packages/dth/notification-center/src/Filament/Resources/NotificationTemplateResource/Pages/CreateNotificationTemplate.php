<?php

namespace Dth\NotificationCenter\Filament\Resources\NotificationTemplateResource\Pages;

use Dth\NotificationCenter\Filament\Resources\NotificationTemplateResource;
use Dth\NotificationCenter\Filament\Support\NotificationPageUi;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateNotificationTemplate extends CreateRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    public function getTitle(): string|Htmlable
    {
        return NotificationPageUi::title(app()->getLocale() === 'en' ? 'Create notification template' : 'Tạo mẫu thông báo', 'template');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(app()->getLocale() === 'en' ? 'Save template' : 'Lưu mẫu')
                ->icon('heroicon-o-check-circle')
                ->extraAttributes(['class' => 'dth-notify-form-action dth-notify-form-action--primary']),
            $this->getCreateAnotherFormAction()
                ->label(app()->getLocale() === 'en' ? 'Save & create another' : 'Lưu & tạo thêm')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-notify-form-action dth-notify-form-action--secondary']),
            $this->getCancelFormAction()
                ->label(app()->getLocale() === 'en' ? 'Cancel' : 'Hủy')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-notify-form-action dth-notify-form-action--secondary']),
        ];
    }
}
