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
        return EmailPageUi::title(
            UiText::get('models.delivery_messages', 'Delivery Log'),
            'log',
            'blue',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('delivery.event_timeline', 'Event timeline');
    }
}
