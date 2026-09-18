<?php

namespace Dth\Email\Filament\Resources\SendingDomainResource\Pages;

use Dth\Email\Filament\Resources\SendingDomainResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditSendingDomain extends EditRecord
{
    protected static string $resource = SendingDomainResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(UiText::get('domain.edit.title', 'Edit Sending Domain'), 'domain', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('domain.edit.subheading', 'Update the sending domain and review its DNS verification status.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(UiText::get('common.actions.delete', 'Delete'))
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(UiText::get('common.actions.save', 'Save'))
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->extraAttributes(['class' => 'dth-email-form-action dth-email-form-action--primary']),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-email-form-action dth-email-form-action--secondary']),
        ];
    }
}
