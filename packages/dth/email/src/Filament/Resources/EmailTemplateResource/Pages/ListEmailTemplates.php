<?php

namespace Dth\Email\Filament\Resources\EmailTemplateResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateCategoryResource;
use Dth\Email\Filament\Resources\EmailTemplateResource;
use Dth\Email\Filament\Resources\EmailTemplateResource\Widgets\TemplateListStats;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;

    public function getTitle(): string|Htmlable
    {
        $title = e(UiText::get('template.list.title', 'Templates Email'));

        return new HtmlString(<<<HTML
            <span class="dth-template-page-title">
                <span class="dth-template-page-title__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 5.75A2.75 2.75 0 0 1 6.75 3h10.5A2.75 2.75 0 0 1 20 5.75v12.5A2.75 2.75 0 0 1 17.25 21H6.75A2.75 2.75 0 0 1 4 18.25V5.75Z" />
                        <path d="M8 8h8M8 12h8M8 16h5" />
                    </svg>
                </span>
                <span>{$title}</span>
            </span>
        HTML);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'template.list.subheading',
            'Quản lý mẫu email, trạng thái và nội dung theo từng giai đoạn marketing.'
        );
    }

    /** @return array<class-string<\Filament\Widgets\Widget>> */
    protected function getHeaderWidgets(): array
    {
        return [
            TemplateListStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageCategories')
                ->label(UiText::get('template.list.manage_categories', 'Quản lý danh mục'))
                ->icon('heroicon-o-folder')
                ->color('gray')
                ->url(fn (): string => EmailTemplateCategoryResource::getUrl('index')),
            Actions\CreateAction::make()
                ->label(UiText::get('common.actions.new', 'Tạo template'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
