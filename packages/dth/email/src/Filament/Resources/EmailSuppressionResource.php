<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Enums\SuppressionReason;
use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\EmailSuppressionResource\Pages;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\EmailSuppression;
use Dth\Email\Services\SuppressionService;
use Dth\Email\Support\UiText;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmailSuppressionResource extends Resource
{
    protected static ?string $model = EmailSuppression::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-no-symbol';
    protected static string|\UnitEnum|null $navigationGroup = EmailNavigationGroup::Email;
    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.suppressions', 'Suppressions', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.suppression', 'Suppression', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.suppressions', 'Suppressions', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('suppression.section', 'Suppression'))
                ->schema([
                    TextInput::make('email')
                        ->label(UiText::get('common.fields.email', 'Email'))
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Textarea::make('note')
                        ->label(UiText::get('common.fields.notes', 'Note'))
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label(UiText::get('common.fields.email', 'Email'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('suppression_status')
                    ->label(UiText::get('suppression.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),

                TextColumn::make('reason')
                    ->label(UiText::get('common.fields.reason', 'Reason'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),

                TextColumn::make('source')
                    ->label(UiText::get('common.fields.source', 'Source'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('note')
                    ->label(UiText::get('common.fields.notes', 'Note'))
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label(UiText::get('suppression.added', 'Added'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('released_at')
                    ->label(UiText::get('suppression.released', 'Released'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('release_source')
                    ->label(UiText::get('suppression.release_source', 'Release source'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('release_note')
                    ->label(UiText::get('suppression.release_note', 'Release note'))
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('reason')
                    ->label(UiText::get('common.fields.reason', 'Reason'))
                    ->options([
                        'manual' => UiText::get('suppression.manual', 'Manual'),
                        'unsubscribe' => UiText::get('suppression.unsubscribe', 'Unsubscribe'),
                        'bounce' => UiText::get('suppression.bounce', 'Bounce'),
                        'complaint' => UiText::get('suppression.complaint', 'Complaint'),
                    ]),
            ])
            ->recordActions([
                Action::make('release')
                    ->label(fn (EmailSuppression $record): string =>
                        $record->reason === SuppressionReason::Unsubscribe
                            ? UiText::get('suppression.resubscribe', 'Resubscribe')
                            : UiText::get('suppression.release', 'Release')
                    )
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (EmailSuppression $record): bool =>
                        app(SuppressionService::class)->canRelease($record)
                    )
                    ->modalDescription(fn (EmailSuppression $record): string =>
                        $record->reason === SuppressionReason::Unsubscribe
                            ? UiText::get(
                                'suppression.resubscribe_description',
                                'This only changes the current subscription state. The original campaign will still keep its historical Unsubscribed metric.'
                            )
                            : UiText::get(
                                'suppression.release_description',
                                'Release this manual suppression while keeping its audit history?'
                            )
                    )
                    ->schema([
                        Select::make('release_source')
                            ->label(UiText::get('suppression.reason_source', 'Reason / source'))
                            ->options([
                                'customer_opt_in' => UiText::get('suppression.customer_opt_in', 'Customer opted in again'),
                                'customer_request' => UiText::get('suppression.customer_request', 'Customer requested resubscription'),
                                'admin_correction' => UiText::get('suppression.admin_correction', 'Administrative correction'),
                                'other' => UiText::get('suppression.other', 'Other'),
                            ])
                            ->native(false)
                            ->required(),
                        Textarea::make('release_note')
                            ->label(UiText::get('suppression.note_evidence', 'Note / evidence'))
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (EmailSuppression $record, array $data): void {
                        app(SuppressionService::class)->release(
                            suppression: $record,
                            releasedBy: auth()->id(),
                            source: $data['release_source'],
                            note: $data['release_note'],
                        );

                        Notification::make()
                            ->title(
                                $record->reason === SuppressionReason::Unsubscribe
                                    ? UiText::get(
                                        'suppression.resubscribed_title',
                                        'Address resubscribed for future campaigns'
                                    )
                                    : UiText::get('suppression.released_title', 'Suppression released')
                            )
                            ->body(UiText::get(
                                'suppression.history_unchanged',
                                'Historical campaign metrics were not changed.'
                            ))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailSuppressions::route('/'),
            'create' => Pages\CreateEmailSuppression::route('/create'),
        ];
    }
}
