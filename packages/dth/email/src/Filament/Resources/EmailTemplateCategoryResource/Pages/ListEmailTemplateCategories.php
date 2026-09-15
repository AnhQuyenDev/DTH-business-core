<?php

namespace Dth\Email\Filament\Resources\EmailTemplateCategoryResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateCategoryResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Models\EmailTemplateCategory;
use Dth\Email\Support\UiText;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEmailTemplateCategories extends ListRecords
{
    protected static string $resource = EmailTemplateCategoryResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(
            UiText::get('template_category.list.title', 'Email Template Categories'),
            'folder',
            'violet',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('template_category.list.subheading', 'Organize email templates into reusable categories.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createCategory')
                ->label(
                    UiText::get('template_category.create.title', 'Create Template Category')
                )
                ->icon('heroicon-o-plus')
                ->modalHeading(
                    UiText::get('template_category.create.title', 'Create Template Category')
                )
                ->modalSubmitActionLabel(UiText::get('common.actions.save', 'Save'))
                ->modalCancelActionLabel(UiText::get('common.actions.cancel', 'Cancel'))
                ->schema([
                    TextInput::make('name')
                        ->label(UiText::get('common.fields.name', 'Name'))
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->label(UiText::get('template_category.active', 'Active'))
                        ->default(true)
                        ->inline(false),
                    Textarea::make('description')
                        ->label(UiText::get('template_category.description', 'Description'))
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    EmailTemplateCategory::query()->create($data);

                    Notification::make()
                        ->title(
                            UiText::get('common.actions.new', 'New').' '.
                            UiText::get('models.template_category', 'Template Category')
                        )
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => EmailTemplateCategoryResource::canCreate()),
        ];
    }
}
