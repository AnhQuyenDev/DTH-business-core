<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Enums\EmailEventType;
use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\EmailDeliveryLogResource\Pages;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Support\UiText;
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
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';
    protected static string|\UnitEnum|null $navigationGroup = EmailNavigationGroup::Email;
    protected static ?int $navigationSort = 60;
    protected static ?string $recordTitleAttribute = 'subject';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.delivery_log', 'Delivery Log', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.delivery_message', 'Email Message', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.delivery_messages', 'Delivery Log', context: 'model');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('recipient_email')
                    ->label(UiText::get('delivery.recipient', 'Recipient'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('subject')
                    ->label(UiText::get('template.subject', 'Subject'))
                    ->searchable()
                    ->limit(55),

                TextColumn::make('campaignRecipient.campaign.name')
                    ->label(UiText::get('delivery.campaign', 'Campaign'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('sendingAccount.name')
                    ->label(UiText::get('delivery.account', 'Account'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),

                TextColumn::make('open_events_count')
                    ->label(UiText::get('delivery.opens', 'Opens'))
                    ->badge(),

                TextColumn::make('click_events_count')
                    ->label(UiText::get('delivery.clicks', 'Clicks'))
                    ->badge(),

                TextColumn::make('sent_at')
                    ->label(UiText::get('delivery.sent', 'Sent'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(collect(EmailMessageStatus::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => UiText::status($case)])
                        ->all()),

                SelectFilter::make('sendingAccount')
                    ->label(UiText::get('delivery.sending_account', 'Sending account'))
                    ->relationship('sendingAccount', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(UiText::get('common.actions.view', 'View'))
                    ->icon('heroicon-o-eye'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make(UiText::get('delivery.message', 'Message'))
                    ->schema([
                        TextEntry::make('recipient_email')
                            ->label(UiText::get('delivery.recipient', 'Recipient')),

                        TextEntry::make('recipient_name')
                            ->label(UiText::get('delivery.name', 'Name'))
                            ->placeholder('—'),

                        TextEntry::make('status')
                            ->label(UiText::get('common.fields.status', 'Status'))
                            ->badge()
                            ->formatStateUsing(fn ($state): string => UiText::status($state))
                            ->color(fn ($state): string => StatusColor::for($state)),

                        TextEntry::make('subject')
                            ->label(UiText::get('template.subject', 'Subject'))
                            ->columnSpanFull(),

                        TextEntry::make('from_email')
                            ->label(UiText::get('delivery.from', 'From')),

                        TextEntry::make('sendingAccount.name')
                            ->label(UiText::get('delivery.sending_account', 'Sending account'))
                            ->placeholder('—'),

                        TextEntry::make('campaignRecipient.campaign.name')
                            ->label(UiText::get('delivery.campaign', 'Campaign'))
                            ->placeholder('—'),

                        TextEntry::make('provider_message_id')
                            ->label(UiText::get('delivery.provider_id', 'Provider ID'))
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

                        TextEntry::make('failure_reason')
                            ->label(UiText::get('delivery.failure', 'Failure'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make(UiText::get('delivery.event_timeline', 'Event timeline'))
                    ->schema([
                        RepeatableEntry::make('events')
                            ->label('')
                            ->schema([
                                TextEntry::make('event_type')
                                    ->label(UiText::get('delivery.event', 'Event'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                                    ->color(fn ($state): string => StatusColor::for($state)),

                                TextEntry::make('occurred_at')
                                    ->label(UiText::get('delivery.time', 'Time'))
                                    ->dateTime('d/m/Y H:i:s'),

                                TextEntry::make('ip_address')
                                    ->label('IP')
                                    ->placeholder('—'),

                                TextEntry::make('provider_event_id')
                                    ->label(UiText::get('delivery.provider_event', 'Provider event'))
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
                'events as open_events_count' => fn (Builder $query) => $query->where(
                    'event_type',
                    EmailEventType::Opened->value
                ),
                'events as click_events_count' => fn (Builder $query) => $query->where(
                    'event_type',
                    EmailEventType::Clicked->value
                ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailDeliveryLogs::route('/'),
            'view' => Pages\ViewEmailDeliveryLog::route('/{record}'),
        ];
    }
}
