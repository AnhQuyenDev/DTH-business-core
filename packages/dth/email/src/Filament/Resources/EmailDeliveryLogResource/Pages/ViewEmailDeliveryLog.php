<?php

namespace Dth\Email\Filament\Resources\EmailDeliveryLogResource\Pages;

use Dth\Email\Filament\Resources\EmailDeliveryLogResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewEmailDeliveryLog extends ViewRecord
{
    protected static string $resource = EmailDeliveryLogResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(UiText::get('delivery_log.view.title', 'Chi tiết nhật ký gửi Email'), 'log', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('delivery_log.view.subheading', 'Xem lại đầy đủ thông tin gửi, phản hồi máy chủ và kết quả xử lý cho email đã chọn.');
    }
}
