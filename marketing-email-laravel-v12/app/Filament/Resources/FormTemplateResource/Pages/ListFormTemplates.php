<?php

namespace App\Filament\Resources\FormTemplateResource\Pages;

use App\Enums\Marketing\FormAudienceType;
use App\Filament\Resources\FormTemplateResource;
use App\Models\Marketing\FormTemplate;
use App\Services\Marketing\LandingPageRenderService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

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
                    TextInput::make('slug')->label(__('field.slug'))->required()->maxLength(255),
                    Select::make('audience_type')
                        ->label(__('field.form_type'))
                        ->options(FormAudienceType::options())
                        ->default(FormAudienceType::Generic->value)
                        ->required(),
                    FileUpload::make('html_file')
                        ->label(__('field.html_file'))
                        ->acceptedFileTypes(['text/html', 'text/plain'])
                        ->required()
                        ->disk('local'),
                ])
                ->action(function (array $data): void {
                    $filePath = storage_path('app/private/'.$data['html_file']);
                    $content = file_get_contents($filePath);
                    @unlink($filePath);
                    $body = app(LandingPageRenderService::class)->extractBodyContent($content);

                    FormTemplate::query()->create([
                        'name' => (string) $data['name'],
                        'slug' => (string) $data['slug'],
                        'audience_type' => (string) $data['audience_type'],
                        'status' => 'draft',
                        'html_body' => $body,
                        'created_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title(__('notification.created'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
