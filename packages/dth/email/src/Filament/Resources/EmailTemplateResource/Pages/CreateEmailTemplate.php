<?php

namespace Dth\Email\Filament\Resources\EmailTemplateResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(
            UiText::get('template.create.title', 'Create Email Template'),
            'template',
            'violet',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'template.create.subheading',
            'Compose content, add personalization variables, and configure the template before saving.'
        );
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
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
