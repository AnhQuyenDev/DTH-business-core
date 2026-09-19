<?php

namespace Dth\NotificationCenter\Filament\Resources\NotificationTemplateResource\Pages;

use Dth\NotificationCenter\Filament\Resources\NotificationTemplateResource;
use Dth\NotificationCenter\Filament\Support\NotificationPageUi;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListNotificationTemplates extends ListRecords
{
    protected static string $resource = NotificationTemplateResource::class;

    public function getTitle(): string|Htmlable
    {
        return NotificationPageUi::title(app()->getLocale() === 'en' ? 'Notification templates' : 'Mẫu thông báo', 'template');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'New template' : 'Tạo mẫu')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-notify-entry-action']),
        ];
    }
}
