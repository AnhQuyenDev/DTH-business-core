<?php

namespace Dth\Email\Filament\Resources\SendingDomainResource\Pages;

use Dth\Email\Filament\Resources\SendingDomainResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateSendingDomain extends CreateRecord
{
    protected static string $resource = SendingDomainResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(
            UiText::get('domain.create.title', 'Create Sending Domain'),
            'domain',
            'blue',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'domain.create.subheading',
            'Configure a sending domain and prepare its DNS verification records.'
        );
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(UiText::get('common.actions.save', 'Save'))
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
