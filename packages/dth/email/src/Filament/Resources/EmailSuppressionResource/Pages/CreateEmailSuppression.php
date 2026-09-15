<?php

namespace Dth\Email\Filament\Resources\EmailSuppressionResource\Pages;

use Dth\Email\Enums\SuppressionReason;
use Dth\Email\Filament\Resources\EmailSuppressionResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Services\SuppressionService;
use Dth\Email\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class CreateEmailSuppression extends CreateRecord
{
    protected static string $resource = EmailSuppressionResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(
            UiText::get('suppression.create.title', 'Add Email to Suppression List'),
            'shield',
            'red',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('suppression.create.subheading', 'Block an email address from future campaigns while preserving audit history.');
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

    protected function handleRecordCreation(array $data): Model
    {
        return app(SuppressionService::class)->suppress(
            email: $data['email'],
            reason: SuppressionReason::Manual,
            source: 'email.admin',
            note: $data['note'] ?? null,
            createdBy: auth()->id(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
