<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SendingAccountResource\Pages;
use App\Models\Crm\Department;
use App\Models\Marketing\SendingAccount;
use App\Services\Marketing\SendingAccountService;
use App\Support\Ui\BadgePalette;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class SendingAccountResource extends Resource
{
    protected static ?string $model = SendingAccount::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.email_marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.sending_account.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.sending_account.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.sending_account.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 50;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.manage-sending') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.sending_account'))
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                    Select::make('department_id')
                        ->label(__('field.department'))
                        ->options(Department::options())
                        ->searchable()
                        ->helperText(__('field.sending_account_department_helper')),
                    Select::make('provider')
                        ->label(__('field.provider'))
                        ->options([
                            'laravel_mail' => __('field.driver_laravel'),
                            'smtp' => __('field.driver_smtp'),
                        ])
                        ->required()
                        ->default('laravel_mail')
                        ->live(),
                    Select::make('status')
                        ->label(__('field.status'))
                        ->options([
                            'active' => __('field.status_active'),
                            'inactive' => __('field.status_inactive'),
                            'testing' => __('field.status_testing'),
                        ])
                        ->default('active')
                        ->required(),
                ]),
            Section::make(__('uiux.section.sender_identity'))
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->schema([
                    TextInput::make('from_name')->label(__('field.from_name'))->required()->maxLength(255),
                    TextInput::make('from_email')->label(__('field.from_email'))->email()->required()->maxLength(255),
                    TextInput::make('reply_to')->label(__('field.reply_to'))->email()->maxLength(255),
                ]),
            Section::make(__('uiux.section.smtp_configuration'))
                ->description(__('helper.smtp_config_keys'))
                ->visible(fn (Get $get): bool => $get('provider') === 'smtp')
                ->schema([
                    KeyValue::make('config_encrypted')
                        ->label(__('field.config_encrypted'))
                        ->keyLabel(__('field.key'))
                        ->valueLabel(__('field.value'))
                        ->reorderable(false)
                        ->columnSpanFull(),
                ]),
            Section::make(__('uiux.section.sending_limits'))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsed()
                ->collapsible()
                ->schema([
                    TextInput::make('daily_limit')->label(__('field.daily_limit'))->numeric()->minValue(0)->default(0),
                    TextInput::make('hourly_limit')->label(__('field.hourly_limit'))->numeric()->minValue(0)->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('provider')->label(__('field.provider'))->badge()
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'laravel_mail' => __('field.driver_laravel'),
                    'smtp' => __('field.driver_smtp'),
                    default => (string) $state,
                })
                ->color(fn ($state): string => match ($state) {
                    'laravel_mail', 'smtp' => 'info',
                    default => 'gray',
                }),
            TextColumn::make('department.name')->label(__('field.department'))->badge()->color(fn (SendingAccount $record): string => $record->department?->color ?? 'gray')->placeholder(__('common.not_available')),
            TextColumn::make('from_email')->label(__('field.from_email'))->searchable(),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn ($state): string => $state ? __('field.status_'.(string) $state) : __('common.not_available'))
                ->color(fn ($state): string => BadgePalette::status($state)),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime('d/m/Y H:i')->sortable(),
        ])
            ->headerActions([
                Action::make('send_test')
                    ->label(__('action.send_test'))
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Select::make('sending_account_id')
                            ->label(__('field.sending_account'))
                            ->options(SendingAccount::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('to_email')->label(__('field.to_email'))->email()->required(),
                        TextInput::make('subject')->label(__('field.subject'))->required()->default(__('field.sending_account_test_subject_default')),
                        TextInput::make('body')->label(__('field.body'))->required()->default(__('field.sending_account_test_body_default')),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $account = SendingAccount::findOrFail((int) $data['sending_account_id']);
                            app(SendingAccountService::class)->sendTestEmail(
                                account: $account,
                                toEmail: (string) $data['to_email'],
                                subject: (string) $data['subject'],
                                body: (string) $data['body'],
                            );

                            Notification::make()
                                ->title(__('notification.test_email_sent'))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title(__('notification.email_test_failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
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
