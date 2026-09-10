<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Enums\SuppressionReason;
use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\EmailSuppressionResource\Pages;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\EmailSuppression;
use Dth\Email\Services\SuppressionService;
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

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-no-symbol';

    protected static string|\UnitEnum|null $navigationGroup =
        EmailNavigationGroup::Email;

    protected static ?string $navigationLabel = 'Suppressions';
    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Suppression')
                ->schema([
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Textarea::make('note')
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
                    ->searchable()
                    ->copyable(),

                TextColumn::make('suppression_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn ($state): string => StatusColor::for($state)),

                TextColumn::make('reason')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ucfirst(
                        $state instanceof SuppressionReason
                            ? $state->value
                            : (string) $state
                    ))
                    ->color(fn ($state): string => StatusColor::for($state)),

                TextColumn::make('source')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('note')
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('released_at')
                    ->label('Released')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('release_source')
                    ->label('Release source')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('release_note')
                    ->label('Release note')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('reason')
                    ->options([
                        'manual' => 'Manual',
                        'unsubscribe' => 'Unsubscribe',
                        'bounce' => 'Bounce',
                        'complaint' => 'Complaint',
                    ]),
            ])
            ->recordActions([
                Action::make('release')
                    ->label(fn (EmailSuppression $record): string =>
                        $record->reason === SuppressionReason::Unsubscribe
                            ? 'Resubscribe'
                            : 'Release'
                    )
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (EmailSuppression $record): bool =>
                        app(SuppressionService::class)->canRelease($record)
                    )
                    ->modalDescription(fn (EmailSuppression $record): string =>
                        $record->reason === SuppressionReason::Unsubscribe
                            ? 'This only changes the current subscription state. The original campaign will still keep its historical Unsubscribed metric.'
                            : 'Release this manual suppression while keeping its audit history?'
                    )
                    ->schema([
                        Select::make('release_source')
                            ->label('Reason / source')
                            ->options([
                                'customer_opt_in' => 'Customer opted in again',
                                'customer_request' => 'Customer requested resubscription',
                                'admin_correction' => 'Administrative correction',
                                'other' => 'Other',
                            ])
                            ->native(false)
                            ->required(),
                        Textarea::make('release_note')
                            ->label('Note / evidence')
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
                                    ? 'Address resubscribed for future campaigns'
                                    : 'Suppression released'
                            )
                            ->body('Historical campaign metrics were not changed.')
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
