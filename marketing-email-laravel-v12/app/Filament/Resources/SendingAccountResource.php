<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SendingAccountResource\Pages;
use App\Models\Crm\Department;
use App\Models\Marketing\SendingAccount;
use App\Services\Marketing\SendingAccountService;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.manage-sending') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.sending_account'))->schema([
                TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                Select::make('department_id')
                    ->label(__('field.department'))
                    ->options(Department::options())
                    ->searchable()
                    ->helperText(__('field.sending_account_department_helper')),
                Select::make('provider')
                    ->options([
                        'laravel_mail' => __('field.driver_laravel'),
                        'smtp' => __('field.driver_smtp'),
                    ])
                    ->required()
                    ->default('laravel_mail'),
                TextInput::make('from_name')->label(__('field.from_name'))->required()->maxLength(255),
                TextInput::make('from_email')->label(__('field.from_email'))->email()->required()->maxLength(255),
                TextInput::make('reply_to')->label(__('field.reply_to'))->email()->maxLength(255),
                KeyValue::make('config_encrypted')
                    ->label(__('field.config_encrypted'))
                    ->keyLabel(__('field.key'))
                    ->valueLabel(__('field.value'))
                    ->columnSpanFull(),
                TextInput::make('daily_limit')->label(__('field.daily_limit'))->numeric()->default(0),
                TextInput::make('hourly_limit')->label(__('field.hourly_limit'))->numeric()->default(0),
                Select::make('status')
                    ->options([
                        'active' => __('field.status_active'),
                        'inactive' => __('field.status_inactive'),
                        'testing' => __('field.status_testing'),
                    ])
                    ->default('active')
                    ->required(),
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
            TextColumn::make('department.name')->label(__('field.department'))->badge()->placeholder(__('common.not_available')),
            TextColumn::make('from_email')->label(__('field.from_email'))->searchable(),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn ($state): string => $state ? __('field.status_'.(string) $state) : __('common.not_available'))
                ->color(fn ($state): string => match ($state) {
                    'active' => 'success',
                    'inactive' => 'danger',
                    'testing' => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable(),
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
                        TextInput::make('to_email')->email()->required(),
                        TextInput::make('subject')->required()->default(__('field.sending_account_test_subject_default')),
                        TextInput::make('body')->required()->default(__('field.sending_account_test_body_default')),
                    ])
                    ->action(function (array $data): void {
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
