<?php

namespace App\Filament\Resources\LandingPageResource\RelationManagers;

use App\Models\Marketing\LandingPageUtmUrl;
use App\Support\UtmOptions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Js;

class UtmUrlsRelationManager extends RelationManager
{
    protected static string $relationship = 'utmUrls';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.utm_urls');
    }

    public function getLabel(): string
    {
        return __('relation.title.utm_urls');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('utm_source')
                    ->options(UtmOptions::source())
                    ->label(__('field.utm_source'))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('utm_medium')
                    ->label(__('field.utm_medium'))
                    ->options(UtmOptions::medium())
                    ->searchable()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('utm_campaign')
                    ->label(__('field.utm_campaign'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('utm_content')
                    ->label(__('field.utm_content'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('utm_term')
                    ->label(__('field.utm_term'))
                    ->maxLength(255),
                Forms\Components\Textarea::make('url')
                    ->label(__('field.utm_generated_url'))
                    ->required()
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('url')
            ->columns([
                Tables\Columns\TextColumn::make('utm_source')
                    ->label(__('field.utm_source'))
                    ->placeholder(__('common.not_available')),
                Tables\Columns\TextColumn::make('utm_medium')
                    ->label(__('field.utm_medium'))
                    ->placeholder(__('common.not_available')),
                Tables\Columns\TextColumn::make('utm_campaign')
                    ->label(__('field.utm_campaign'))
                    ->placeholder(__('common.not_available')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime()
                    ->placeholder(__('common.not_available')),
                Tables\Columns\TextColumn::make('url')
                    ->label(__('field.utm_generated_url'))
                    ->copyable()
                    ->copyableState(fn (Tables\Columns\TextColumn $column, $record) => $record->url)
                    ->copyMessage(__('notification.url_copied'))
                    ->limit(50),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([ActionGroup::make([
                Tables\Actions\Action::make('copy_url')
                    ->label(__('action.copy_url'))
                    ->icon('heroicon-o-clipboard-document')
                    ->color('info')
                    ->action(function (LandingPageUtmUrl $record, $livewire): void {
                        $livewire->js('navigator.clipboard.writeText('.(string) Js::from($record->url).')');
                        Notification::make()
                            ->title(__('notification.url_copied'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
