<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Enums\EmailEventType;
use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\EmailDeliveryLogResource\Pages;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Filament\Support\StatusColor;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailDeliveryLogResource extends Resource
{
    protected static ?string $model = EmailMessage::class;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-inbox-stack';

    protected static string|\UnitEnum|null $navigationGroup =
        EmailNavigationGroup::Email;

    protected static ?string $navigationLabel =
        'Delivery Log';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute =
        'subject';

    public static function table(
        Table $table,
    ): Table {
        return $table
            ->columns([
                TextColumn::make('recipient_email')
                    ->label('Recipient')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('subject')
                    ->searchable()
                    ->limit(55),

                TextColumn::make(
                    'campaignRecipient.campaign.name'
                )
                    ->label('Campaign')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make(
                    'sendingAccount.name'
                )
                    ->label('Account')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string =>
                            ucfirst(
                                $state instanceof EmailMessageStatus
                                    ? $state->value
                                    : (string) $state
                            )
                    )
                    ->color(
                        fn ($state): string =>
                            StatusColor::for($state)
                    ),

                TextColumn::make('open_events_count')
                    ->label('Opens')
                    ->badge(),

                TextColumn::make('click_events_count')
                    ->label('Clicks')
                    ->badge(),

                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'queued' => 'Queued',
                        'sending' => 'Sending',
                        'sent' => 'Sent',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                        'bounced' => 'Bounced',
                        'complained' => 'Complained',
                        'suppressed' => 'Suppressed',
                    ]),

                SelectFilter::make('sendingAccount')
                    ->label('Sending account')
                    ->relationship(
                        'sendingAccount',
                        'name'
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function infolist(
        Schema $schema,
    ): Schema {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Message')
                    ->schema([
                        TextEntry::make(
                            'recipient_email'
                        )
                            ->label('Recipient'),

                        TextEntry::make(
                            'recipient_name'
                        )
                            ->label('Name')
                            ->placeholder('—'),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(
                                fn ($state): string =>
                                    ucfirst(
                                        $state instanceof EmailMessageStatus
                                            ? $state->value
                                            : (string) $state
                                    )
                            )
                            ->color(
                                fn ($state): string =>
                                    StatusColor::for($state)
                            ),

                        TextEntry::make('subject')
                            ->columnSpanFull(),

                        TextEntry::make(
                            'from_email'
                        )
                            ->label('From'),

                        TextEntry::make(
                            'sendingAccount.name'
                        )
                            ->label('Sending account')
                            ->placeholder('—'),

                        TextEntry::make(
                            'campaignRecipient.campaign.name'
                        )
                            ->label('Campaign')
                            ->placeholder('—'),

                        TextEntry::make(
                            'provider_message_id'
                        )
                            ->label('Provider ID')
                            ->placeholder('—')
                            ->copyable(),

                        TextEntry::make('queued_at')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('—'),

                        TextEntry::make('sent_at')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('—'),

                        TextEntry::make('delivered_at')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('—'),

                        TextEntry::make('failed_at')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('—'),

                        TextEntry::make(
                            'failure_reason'
                        )
                            ->label('Failure')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make('Event timeline')
                    ->schema([
                        RepeatableEntry::make('events')
                            ->label('')
                            ->schema([
                                TextEntry::make(
                                    'event_type'
                                )
                                    ->label('Event')
                                    ->badge()
                                    ->formatStateUsing(
                                        fn ($state): string =>
                                            ucfirst(
                                                $state instanceof EmailEventType
                                                    ? $state->value
                                                    : (string) $state
                                            )
                                    ),

                                TextEntry::make(
                                    'occurred_at'
                                )
                                    ->label('Time')
                                    ->dateTime(
                                        'd/m/Y H:i:s'
                                    ),

                                TextEntry::make(
                                    'ip_address'
                                )
                                    ->label('IP')
                                    ->placeholder('—'),

                                TextEntry::make(
                                    'provider_event_id'
                                )
                                    ->label('Provider event')
                                    ->placeholder('—'),
                            ])
                            ->columns(4),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'sendingAccount',
                'campaignRecipient.campaign',
            ])
            ->withCount([
                'events as open_events_count' =>
                    fn (Builder $query) =>
                        $query->where(
                            'event_type',
                            EmailEventType::Opened->value
                        ),

                'events as click_events_count' =>
                    fn (Builder $query) =>
                        $query->where(
                            'event_type',
                            EmailEventType::Clicked->value
                        ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListEmailDeliveryLogs::route('/'),

            'view' =>
                Pages\ViewEmailDeliveryLog::route(
                    '/{record}'
                ),
        ];
    }

}