<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\MarketingCampaign;
use App\Services\Marketing\LandingPageImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;
use Throwable;

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
                    $data['slug'] = Str::slug((string) $data['name']);
                    $filePath = storage_path(
                        'app/private/'.$data['html_file']
                    );

                    try {
                        if (! is_file($filePath)) {
                            throw new \RuntimeException(
                                __('notification.landing_html_missing')
                            );
                        }

                        $content = file_get_contents($filePath);

                        if ($content === false) {
                            throw new \RuntimeException(
                                __('notification.landing_html_unreadable')
                            );
                        }

                        app(LandingPageImportService::class)->import(
                            data: $data,
                            sourceHtml: $content,
                            userId: auth()->id(),
                        );

                        Notification::make()
                            ->title(__('notification.landing_page_created'))
                            ->body(
                                __('notification.landing_embedded_form_removed')
                                .__('notification.landing_attach_forms')
                            )
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title(__('notification.landing_page_import_failed'))
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
