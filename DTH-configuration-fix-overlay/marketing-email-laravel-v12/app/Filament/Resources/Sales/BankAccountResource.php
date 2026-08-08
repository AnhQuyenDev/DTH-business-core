<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\BankAccountResource\Pages;
use App\Models\Sales\BankAccount;
use App\Models\VnBank;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getModelLabel(): string
    {
        return __('resource.bank_account.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.bank_account.plural');
    }

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('sales.view-bank-accounts') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('sales.manage-bank-accounts') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('sales.manage-bank-accounts') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('sales.manage-bank-accounts') ?? false;
    }

    public static function form(Form $form): Form
    {
        $statusOptions = [
            'active' => __('field.status_active'),
            'inactive' => __('field.status_inactive'),
        ];

        return $form->schema([
            Section::make(__('section.bank_account_details'))->schema([
                Select::make('bank_code')
                    ->label(__('field.bank'))
                    ->options(VnBank::query()->orderBy('short_name')->pluck('name', 'code'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $bank = VnBank::where('code', $state)->first();
                        $set('bank_name', $bank?->name ?? $state);
                        $set('swift_code', $bank?->swift_code ?? null);
                    }),
                Hidden::make('bank_name'),
                Hidden::make('swift_code'),
                TextInput::make('account_number')->label(__('field.account_number'))->required()->maxLength(50),
                TextInput::make('account_name')->label(__('field.account_name'))->required()->maxLength(255),
                Select::make('status')
                    ->label(__('field.status'))
                    ->options($statusOptions)
                    ->default('active')
                    ->required(),
                Toggle::make('is_default')->label(__('field.is_default')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('bank_code')->label(__('field.bank_code'))->searchable(),
            TextColumn::make('bank_name')->label(__('field.bank_name'))->searchable()->sortable(),
            TextColumn::make('account_number')->label(__('field.account_number'))->searchable(),
            TextColumn::make('account_name')->label(__('field.account_name'))->searchable(),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn ($state): string => $state === 'active' ? __('field.status_active') : __('field.status_inactive'))
                ->color(fn ($state): string => $state === 'active' ? 'success' : 'danger'),
            IconColumn::make('is_default')->label(__('field.is_default'))->boolean(),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable()->toggleable(),
        ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('action.bulk_delete'))
                        ->modalHeading(__('action.bulk_delete'))
                        ->requiresConfirmation()
                        ->visible(
                            fn (): bool => auth()->user()?->can(
                                'sales.manage-bank-accounts'
                            ) ?? false
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
