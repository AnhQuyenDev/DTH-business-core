<?php

namespace App\Filament\Resources\FormTemplateResource\Pages;

use App\Enums\Marketing\FormAudienceType;
use App\Filament\Resources\FormTemplateResource;
use App\Services\Marketing\FormTemplateImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;
use Throwable;

class ListFormTemplates extends ListRecords
{
    protected static string $resource = FormTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('action.new_form')),
            Action::make('import_html')
                ->label(__('action.import_html'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                    Select::make('audience_type')
                        ->label(__('field.form_type'))
                        ->options([
                            FormAudienceType::Personal->value => FormAudienceType::Personal->label(),
                            FormAudienceType::Business->value => FormAudienceType::Business->label(),
                        ])
                        ->default(FormAudienceType::Personal->value)
                        ->required(),
                    FileUpload::make('html_file')
                        ->label(__('field.html_file'))
                        ->acceptedFileTypes(['text/html', 'text/plain'])
                        ->required()
                        ->disk('local'),
                ])
                ->action(function (array $data): void {
                    $data['slug'] = Str::slug((string) $data['name']);
                    $filePath = storage_path(
                        'app/private/'.$data['html_file']
                    );

                    try {
                        if (! is_file($filePath)) {
                            throw new \RuntimeException(
                                __('notification.form_html_missing')
                            );
                        }

                        $content = file_get_contents($filePath);

                        if ($content === false) {
                            throw new \RuntimeException(
                                __('notification.form_html_unreadable')
                            );
                        }

                        $template = app(
                            FormTemplateImportService::class
                        )->import(
                            data: $data,
                            sourceHtml: $content,
                            userId: auth()->id(),
                        );

                        Notification::make()
                            ->title(__('notification.form_template_import_success'))
                            ->body(
                                __('notification.form_fields_created', ['count' => $template->fields->count()]).' '.
                                __('notification.form_import_draft')
                            )
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title(__('notification.form_template_import_failed'))
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        if (is_file($filePath)) {
                            unlink($filePath);
                        }
                    }
                }),
        ];
    }
}
