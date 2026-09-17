<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Enums\CustomerStatus;
use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\CustomerResource\Pages;
use Dth\Crm\Filament\Resources\CustomerResource\RelationManagers\AssignmentsRelationManager;
use Dth\Crm\Filament\Resources\CustomerResource\RelationManagers\InteractionsRelationManager;
use Dth\Crm\Models\Customer;
use Dth\Crm\Services\CustomerDistributionService;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\StatusColor;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.customers', 'Customers', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.customer', 'Customer', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.customers', 'Customers', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.customer', 'Customer'))
                ->schema([
                    TextInput::make('customer_code')
                        ->label(UiText::get('fields.customer_code', 'Customer code'))
                        ->disabled()
                        ->dehydrated(false)
                        ->hiddenOn('create'),
                    Select::make('customer_type')
                        ->label(UiText::get('fields.customer_type', 'Customer type'))
                        ->options(CrmOptions::contactTypes())
                        ->native(false)
                        ->required(),
                    TextInput::make('display_name')
                        ->label(UiText::get('fields.customer_name', 'Customer name'))
                        ->required(),
                    TextInput::make('email')
                        ->label(UiText::get('common.fields.email', 'Email'))
                        ->email(),
                    TextInput::make('phone')
                        ->label(UiText::get('fields.phone', 'Phone'))
                        ->tel(),
                    Select::make('status')
                        ->label(UiText::get('common.fields.status', 'Status'))
                        ->options(CustomerStatus::options())
                        ->native(false),
                    Select::make('priority')
                        ->label(UiText::get('fields.priority', 'Priority'))
                        ->options(CrmOptions::priorities())
                        ->native(false),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_code')
                    ->label(UiText::get('fields.customer_code', 'Customer code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label(UiText::get('models.customer', 'Customer'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_type')
                    ->label(UiText::get('fields.customer_type', 'Customer type'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('contact_type', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'contact_type')),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('customer_status', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'customer_status')),
                TextColumn::make('priority')
                    ->label(UiText::get('fields.priority', 'Priority'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('priority', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'priority'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_revenue')
                    ->label(UiText::get('fields.total_revenue', 'Revenue'))
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, ',', '.').' ₫')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_type')
                    ->label(UiText::get('fields.customer_type', 'Customer type'))
                    ->options(CrmOptions::contactTypes()),
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(CustomerStatus::options()),
                SelectFilter::make('priority')
                    ->label(UiText::get('fields.priority', 'Priority'))
                    ->options(CrmOptions::priorities()),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->icon('heroicon-o-eye')
                        ->label(UiText::get('common.actions.view', 'View')),
                    Actions\EditAction::make()->icon('heroicon-o-pencil-square')
                        ->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()->icon('heroicon-o-trash')
                        ->label(UiText::get('common.actions.delete', 'Delete')),
                ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('distribute')
                        ->label(UiText::get('actions.distribute_customers', 'Distribute customers'))
                        ->icon('heroicon-o-arrows-right-left')
                        ->requiresConfirmation()
                        ->modalSubmitAction(fn ($action) => $action->icon('heroicon-o-arrows-right-left'))
                        ->modalCancelAction(fn ($action) => $action->icon('heroicon-o-x-mark')->color('gray'))
                        ->action(function ($records): void {
                            app(CustomerDistributionService::class)->distribute($records->modelKeys(), auth()->id());

                            Notification::make()
                                ->success()
                                ->title(UiText::get('notifications.customers_distributed', 'Customers distributed'))
                                ->send();
                        }),
                    Actions\DeleteBulkAction::make()->icon('heroicon-o-trash')
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            InteractionsRelationManager::class,
            AssignmentsRelationManager::class,
        ];
    }
}
