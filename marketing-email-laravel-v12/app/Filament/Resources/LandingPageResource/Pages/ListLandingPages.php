<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\MarketingCampaign;
use App\Services\Marketing\LandingPageRenderService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListLandingPages extends ListRecords
{
    protected static string $resource = LandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('action.new_landing_page')),
            Action::make('import_html')
                ->label(__('action.import_html'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                    TextInput::make('slug')->label(__('field.slug'))->required()->maxLength(255),
                    Select::make('marketing_campaign_id')
                        ->label(__('field.marketing_campaign'))
                        ->options(MarketingCampaign::query()->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload(),
                    Select::make('campaign_id')
                        ->label(__('field.linked_email_campaign'))
                        ->options(Campaign::query()->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload(),
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

                    LandingPage::query()->create([
                        'name' => (string) $data['name'],
                        'slug' => (string) $data['slug'],
                        'marketing_campaign_id' => $data['marketing_campaign_id'] ?? null,
                        'campaign_id' => $data['campaign_id'] ?? null,
                        'html_body' => $body,
                        'status' => 'draft',
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
