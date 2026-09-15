<?php

namespace Dth\Email\Filament\Resources\EmailDeliveryLogResource\Pages;

use Dth\Email\Filament\Resources\EmailDeliveryLogResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEmailDeliveryLogs extends ListRecords
{
    protected static string $resource = EmailDeliveryLogResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(UiText::get('delivery_log.list.title', 'Giám sát gửi Email'), 'log', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('delivery_log.list.subheading', 'Theo dõi từng lượt gửi, trạng thái xử lý và các sự kiện phát sinh trong quá trình delivery.');
    }
}
