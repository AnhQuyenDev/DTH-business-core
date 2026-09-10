<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Filament\Resources\SendingAccountResource\Pages;
use Dth\Email\Filament\Support\FormHelp;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\SendingAccountTestService;
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
    protected static ?string $navigationLabel = 'Sending Accounts';
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Identity')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\Select::make('sending_domain_id')
                            ->label('Domain')
                            ->relationship('domain', 'domain')
                            ->searchable()
                            ->preload()
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\Select::make('status')
                            ->options(collect(SendingAccountStatus::cases())
                                ->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)])
                                ->all())
                            ->required()
                            ->default(SendingAccountStatus::Active->value)
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\TextInput::make('from_name')
                            ->label('From name')
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\TextInput::make('from_email')
                            ->label('From email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\TextInput::make('reply_to')
                            ->label('Reply-to')
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
                            ->label('Host')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 5]),
                        Forms\Components\TextInput::make('smtp_port')
                            ->label('Port')
                            ->numeric()
                            ->required()
                            ->default(587)
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        Forms\Components\Select::make('smtp_scheme')
                            ->label('Security')
                            ->options([
                                'smtp' => 'SMTP / STARTTLS',
                                'smtps' => 'SMTPS',
                            ])
                            ->required()
                            ->default('smtp')
                            ->afterLabel([
                                FormHelp::icon('Use SMTP / STARTTLS for ports such as 587. Use SMTPS when the provider requires implicit TLS, commonly port 465.'),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        Forms\Components\TextInput::make('smtp_timeout')
                            ->label('Timeout')
                            ->numeric()
                            ->suffix('sec')
                            ->default(30)
                            ->columnSpan(['default' => 1, 'md' => 3]),
                        Forms\Components\TextInput::make('smtp_username')
                            ->label('Username')
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 6]),
                        Forms\Components\TextInput::make('smtp_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->afterLabel([
                                FormHelp::icon('Credentials are encrypted before storage. When editing, leave this field empty to keep the current password.'),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 6]),
                        Forms\Components\TextInput::make('smtp_local_domain')
                            ->label('Local domain')
                            ->maxLength(255)
                            ->afterLabel([
                                FormHelp::icon('Optional EHLO/HELO domain. Leave blank unless your SMTP provider requires a specific value.'),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 12])
                    ->columnSpanFull(),

                Section::make('Sending limits')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->description('Optional safety limits. Leave empty if the SMTP provider controls limits externally.')
                    ->schema([
                        Forms\Components\TextInput::make('hourly_limit')
                            ->label('Hourly limit')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('daily_limit')
                            ->label('Daily limit')
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
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('from_email')->searchable(),
                Tables\Columns\TextColumn::make('domain.domain')->label('Domain'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => StatusColor::for($state)),
                Tables\Columns\TextColumn::make('last_test_status')
                    ->label('Last test')
                    ->badge()
                    ->color(fn ($state): string => StatusColor::for($state)),
                Tables\Columns\TextColumn::make('last_tested_at')->dateTime()->sortable(),
            ])
            ->actions([
                Actions\Action::make('testSmtp')
                    ->label('Test')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Forms\Components\TextInput::make('recipient_email')->label('Recipient email')->email()->required(),
                        Forms\Components\TextInput::make('recipient_name')->label('Recipient name'),
                    ])
                    ->action(function (SendingAccount $record, array $data, SendingAccountTestService $service): void {
                        try {
                            $service->sendTest($record, $data['recipient_email'], $data['recipient_name'] ?? null);
                            Notification::make()->title('SMTP test email sent')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('SMTP test failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\EditAction::make()
                    ->label('Edit')
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
