<?php

namespace Dth\Email\Filament\Resources\EmailTemplateCategoryResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateCategoryResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateEmailTemplateCategory extends CreateRecord
{
    protected static string $resource = EmailTemplateCategoryResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(UiText::get('template_category.create.title', 'Tạo danh mục Email'), 'folder', 'violet');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('template_category.create.subheading', 'Tạo mới danh mục để tổ chức và quản lý template email theo nhóm nội dung.');
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
