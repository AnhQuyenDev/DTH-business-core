<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\EmailTemplateCategory;
use App\Services\Marketing\LandingPageRenderService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('action.new_template')),
            Action::make('import_html')
                ->label(__('action.import_html'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                    Select::make('category_id')
                        ->label(__('field.category'))
                        ->options(\App\Models\Marketing\EmailTemplateCategory::query()->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->default(fn () => \App\Models\Marketing\EmailTemplateCategory::where('slug', 'marketing')->first()?->id)
                        ->required(),
                    TextInput::make('subject')->label(__('field.subject'))->required()->maxLength(255),
                    TextInput::make('preheader')->label(__('field.preheader'))->maxLength(255),
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

                    EmailTemplate::query()->create([
                        'name' => (string) $data['name'],
                        'category_id' => (int) ($data['category_id'] ?? EmailTemplateCategory::where('slug', 'marketing')->first()?->id),
                        'subject' => (string) $data['subject'],
                        'preheader' => (string) ($data['preheader'] ?? ''),
                        'html_body' => $body,
                        'text_body' => trim(strip_tags($body)),
                        'status' => 'draft',
                        'created_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title(__('notification.template_created'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
