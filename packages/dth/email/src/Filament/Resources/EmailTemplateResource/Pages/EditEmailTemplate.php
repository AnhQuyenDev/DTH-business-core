<?php

namespace Dth\Email\Filament\Resources\EmailTemplateResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(
            UiText::get('template.edit.title', 'Edit Email Template'),
            'template',
            'violet',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'template.edit.subheading',
            'Update template content, personalization variables, and usage status.'
        );
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
