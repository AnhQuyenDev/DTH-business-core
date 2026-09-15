<?php

namespace Dth\Email\Filament\Resources\EmailTemplateCategoryResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateCategoryResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEmailTemplateCategories extends ListRecords
{
    protected static string $resource = EmailTemplateCategoryResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(UiText::get('template_category.list.title', 'Chi tiết danh mục mẫu'), 'folder', 'violet');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('template_category.list.subheading', 'Quản lý danh mục, mô tả và trạng thái sử dụng cho hệ thống email template.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(UiText::get('common.actions.new', 'Thêm danh mục'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
