<?php

namespace Dth\NotificationCenter\Filament\Resources\NotificationTemplateResource\Pages;

use Dth\NotificationCenter\Filament\Resources\NotificationTemplateResource;
use Dth\NotificationCenter\Filament\Support\NotificationPageUi;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditNotificationTemplate extends EditRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    public function getTitle(): string|Htmlable
    {
        return NotificationPageUi::title(app()->getLocale() === 'en' ? 'Edit notification template' : 'Chỉnh sửa mẫu thông báo', 'template');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(app()->getLocale() === 'en' ? 'Save changes' : 'Lưu thay đổi')
                ->icon('heroicon-o-check-circle')
                ->extraAttributes(['class' => 'dth-notify-form-action dth-notify-form-action--primary']),
            $this->getCancelFormAction()
                ->label(app()->getLocale() === 'en' ? 'Cancel' : 'Hủy')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-notify-form-action dth-notify-form-action--secondary']),
        ];
    }
}
