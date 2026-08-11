<?php

namespace App\Filament\Resources\Sales;

use App\Enums\Sales\PackageStatus;
use App\Filament\Resources\Sales\ServiceProductResource\Pages;
use App\Models\Sales\ServiceProduct;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServiceProductResource extends Resource
{
    protected static ?string $model = ServiceProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?int $navigationSort = 25;
    public static function getNavigationGroup(): string { return __('navigation.group.sales'); }
    public static function getNavigationLabel(): string { return __('v1.catalog.products'); }
    public static function getModelLabel(): string { return __('v1.catalog.product'); }
    public static function getPluralModelLabel(): string { return __('v1.catalog.products'); }
    public static function canViewAny(): bool { return auth()->user()?->can('sales.view-products') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('sales.manage-products') ?? false; }
    public static function canEdit(Model $record): bool { return auth()->user()?->can('sales.manage-products') ?? false; }
    public static function canDelete(Model $record): bool { return auth()->user()?->can('sales.manage-products') ?? false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('v1.catalog.product_information'))->columns(3)->schema([
                TextInput::make('product_code')->label(__('v1.catalog.product_code'))->required()->unique(ignoreRecord: true)->maxLength(50),
                TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                Select::make('status')->label(__('field.status'))->options(PackageStatus::options())->default('active')->required()->native(false),
                Select::make('service_id')->relationship('service','name')->label(__('resource.service.singular'))->searchable()->preload()->required(),
                TextInput::make('unit')->label(__('field.unit'))->default('đơn vị')->required(),
                TextInput::make('default_quantity')->label(__('field.default_quantity'))->numeric()->minValue(1)->default(1)->required(),
                Textarea::make('description')->label(__('field.description'))->rows(3)->columnSpanFull(),
                Select::make('packages')->label(__('v1.catalog.in_packages'))->relationship('packages','name')->multiple()->searchable()->preload()->columnSpanFull(),
            ]),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('product_code')->label(__('v1.catalog.product_code'))->searchable()->sortable(),
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('service.name')->label(__('resource.service.singular'))->sortable(),
            TextColumn::make('unit')->label(__('field.unit')),
            TextColumn::make('status')->label(__('field.status'))->badge()->formatStateUsing(fn ($state) => $state instanceof \BackedEnum && method_exists($state,'label') ? $state->label() : (string)$state),
        ])->actions([ActionGroup::make([EditAction::make(),DeleteAction::make()])->iconButton()]);
    }
    public static function getPages(): array { return ['index'=>Pages\ListServiceProducts::route('/'),'create'=>Pages\CreateServiceProduct::route('/create'),'edit'=>Pages\EditServiceProduct::route('/{record}/edit')]; }
}
