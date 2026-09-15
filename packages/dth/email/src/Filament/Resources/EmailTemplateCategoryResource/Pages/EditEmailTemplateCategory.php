<?php

namespace Dth\Email\Filament\Resources\EmailTemplateCategoryResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateCategoryResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditEmailTemplateCategory extends EditRecord
{
    protected static string $resource = EmailTemplateCategoryResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(
            UiText::get('template_category.edit.title', 'Edit Template Category'),
            'folder',
            'violet',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('template_category.edit.subheading', 'Update the category information used to organize email templates.');
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
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
