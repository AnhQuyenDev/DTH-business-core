<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\SendingAccountResource\Pages;
use Dth\Email\Filament\Support\FormHelp;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\SendingAccountTestService;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Throwable;

class SendingAccountResource extends Resource
{
    protected static ?string $model = SendingAccount::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';
    protected static string|\UnitEnum|null $navigationGroup = EmailNavigationGroup::Email;
    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.sending_accounts', 'Sending Accounts', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.sending_account', 'Sending Account', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.sending_accounts', 'Sending Accounts', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make(UiText::get('account.identity', 'Identity'))
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(UiText::get('common.fields.name', 'Name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\Select::make('sending_domain_id')
                            ->label(UiText::get('account.domain', 'Domain'))
                            ->relationship('domain', 'domain')
                            ->searchable()
                            ->preload()
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\Select::make('status')
                            ->label(UiText::get('common.fields.status', 'Status'))
                            ->options(collect(SendingAccountStatus::cases())
                                ->mapWithKeys(fn ($case) => [$case->value => UiText::status($case)])
                                ->all())
                            ->required()
                            ->default(SendingAccountStatus::Active->value)
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\TextInput::make('from_name')
                            ->label(UiText::get('account.from_name', 'From name'))
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\TextInput::make('from_email')
                            ->label(UiText::get('account.from_email', 'From email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\TextInput::make('reply_to')
                            ->label(UiText::get('account.reply_to', 'Reply-to'))
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 4]),
                    ])
                    ->columns(['default' => 1, 'md' => 12])
                    ->columnSpanFull(),

                Section::make('SMTP')
                    ->icon('heroicon-o-server-stack')
                    ->schema([
                        Forms\Components\Hidden::make('provider')->default('smtp'),
                        Forms\Components\TextInput::make('smtp_host')
                            ->label(UiText::get('account.host', 'Host'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 5]),
                        Forms\Components\TextInput::make('smtp_port')
                            ->label(UiText::get('account.port', 'Port'))
                            ->numeric()
                            ->required()
                            ->default(587)
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        Forms\Components\Select::make('smtp_scheme')
                            ->label(UiText::get('account.security', 'Security'))
                            ->options([
                                'smtp' => 'SMTP / STARTTLS',
                                'smtps' => 'SMTPS',
                            ])
                            ->required()
                            ->default('smtp')
                            ->afterLabel([
                                FormHelp::icon(UiText::get(
                                    'account.smtp_security_help',
                                    'Use SMTP / STARTTLS for ports such as 587. Use SMTPS when the provider requires implicit TLS, commonly port 465.'
                                )),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        Forms\Components\TextInput::make('smtp_timeout')
                            ->label(UiText::get('account.timeout', 'Timeout'))
                            ->numeric()
                            ->suffix('sec')
                            ->default(30)
                            ->columnSpan(['default' => 1, 'md' => 3]),
                        Forms\Components\TextInput::make('smtp_username')
                            ->label(UiText::get('account.username', 'Username'))
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 6]),
                        Forms\Components\TextInput::make('smtp_password')
                            ->label(UiText::get('account.password', 'Password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->afterLabel([
                                FormHelp::icon(UiText::get(
                                    'account.password_help',
                                    'Credentials are encrypted before storage. When editing, leave this field empty to keep the current password.'
                                )),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 6]),
                        Forms\Components\TextInput::make('smtp_local_domain')
                            ->label(UiText::get('account.local_domain', 'Local domain'))
                            ->maxLength(255)
                            ->afterLabel([
                                FormHelp::icon(UiText::get(
                                    'account.local_domain_help',
                                    'Optional EHLO/HELO domain. Leave blank unless your SMTP provider requires a specific value.'
                                )),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 12])
                    ->columnSpanFull(),

                Section::make(UiText::get('account.limits', 'Sending limits'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->description(UiText::get(
                        'account.limits_description',
                        'Optional safety limits. Leave empty if the SMTP provider controls limits externally.'
                    ))
                    ->schema([
                        Forms\Components\TextInput::make('hourly_limit')
                            ->label(UiText::get('account.hourly_limit', 'Hourly limit'))
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('daily_limit')
                            ->label(UiText::get('account.daily_limit', 'Daily limit'))
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('from_email')
                    ->label(UiText::get('account.from_email', 'From email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('domain.domain')
                    ->label(UiText::get('account.domain', 'Domain')),
                Tables\Columns\TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                Tables\Columns\TextColumn::make('last_test_status')
                    ->label(UiText::get('account.last_test', 'Last test'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                Tables\Columns\TextColumn::make('last_tested_at')
                    ->label(UiText::get('account.last_test', 'Last test'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Actions\Action::make('testSmtp')
                    ->label(UiText::get('common.actions.test', 'Test'))
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Forms\Components\TextInput::make('recipient_email')
                            ->label(UiText::get('account.recipient_email', 'Recipient email'))
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('recipient_name')
                            ->label(UiText::get('account.recipient_name', 'Recipient name')),
                    ])
                    ->action(function (SendingAccount $record, array $data, SendingAccountTestService $service): void {
                        try {
                            $service->sendTest($record, $data['recipient_email'], $data['recipient_name'] ?? null);
                            Notification::make()
                                ->title(UiText::get('account.test_sent', 'SMTP test email sent'))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title(UiText::get('account.test_failed', 'SMTP test failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\EditAction::make()
                    ->label(UiText::get('common.actions.edit', 'Edit'))
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSendingAccounts::route('/'),
            'create' => Pages\CreateSendingAccount::route('/create'),
            'edit' => Pages\EditSendingAccount::route('/{record}/edit'),
        ];
    }
}
