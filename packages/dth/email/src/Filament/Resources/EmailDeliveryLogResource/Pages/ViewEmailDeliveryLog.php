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
        return EmailPageUi::title(
            UiText::get('common.actions.view', 'View').' '.UiText::get('models.delivery_message', 'Email Message'),
            'log',
            'blue',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('delivery.message', 'Message');
    }
}
