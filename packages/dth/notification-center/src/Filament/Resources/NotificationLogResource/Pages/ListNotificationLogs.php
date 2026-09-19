<?php

namespace Dth\NotificationCenter\Filament\Resources\NotificationLogResource\Pages;

use Dth\NotificationCenter\Filament\Resources\NotificationLogResource;
use Dth\NotificationCenter\Filament\Support\NotificationPageUi;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListNotificationLogs extends ListRecords
{
    protected static string $resource = NotificationLogResource::class;

    public function getTitle(): string|Htmlable
    {
        return NotificationPageUi::title(app()->getLocale() === 'en' ? 'Delivery log' : 'Nhật ký gửi', 'log');
    }
}
