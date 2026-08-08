<?php

namespace App\Filament\Resources\Sales;

use App\Enums\Sales\AudienceType;
use App\Enums\Sales\BillingPeriodUnit;
use App\Enums\Sales\PackageStatus;
use App\Filament\Resources\Sales\ServicePackageResource\Pages;
use App\Models\Sales\ServicePackage;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServicePackageResource extends Resource
{
    protected static ?string $model = ServicePackage::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getModelLabel(): string
    {
        return __('resource.service_package.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.service_package.plural');
    }

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('sales.view-service-packages') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('sales.manage-service-packages') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('sales.manage-service-packages') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('sales.manage-service-packages') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.package_details'))->schema([
                Select::make('service_id')
                    ->label(__('resource.service.singular'))
                    ->relationship('service', 'name')
                    ->required(),
                TextInput::make('package_code')->label(__('field.package_code'))->required()->maxLength(30)->unique(ignoreRecord: true),
                TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                Textarea::make('description')->label(__('field.description'))->rows(3),
                Select::make('audience_type')
                    ->label(__('field.audience_type'))
                    ->options(AudienceType::options())
                    ->default('both')
                    ->required(),
                TextInput::make('billing_period')->label(__('field.billing_period'))->numeric()->minValue(1)->nullable(),
                Select::make('billing_period_unit')
                    ->label(__('field.billing_period_unit'))
                    ->options(BillingPeriodUnit::options())
                    ->nullable(),
                TextInput::make('unit')
                    ->label(__('field.unit'))
                    ->required()
                    ->maxLength(50)
                    ->default('gói')
                    ->datalist([
                        'gói',
                        'tháng',
                        'năm',
                        'lần',
                        'tài khoản',
                        'người dùng',
                        'máy chủ',
                        'tên miền',
                        'GB',
                        'TB',
                        'license',
                    ])
                    ->helperText('Đây là đơn vị số lượng của dịch vụ (ví dụ: gói, tháng, lần), không phải đơn vị tiền tệ như VND/USD.'),
                TextInput::make('default_quantity')
                    ->label(__('field.default_quantity'))
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                Select::make('status')
                    ->label(__('field.status'))
                    ->options(PackageStatus::options())
                    ->default('active')
                    ->required(),
                TextInput::make('sort_order')->label(__('field.sort_order'))->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('package_code')->label(__('field.package_code'))->searchable()->sortable(),
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('service.name')->label(__('resource.service.singular'))->sortable(),
            TextColumn::make('audience_type')->label(__('field.audience_type'))->badge()
                ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
            TextColumn::make('sort_order')->label(__('field.sort_order'))->sortable(),
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
                                'sales.manage-service-packages'
                            ) ?? false
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServicePackages::route('/'),
            'create' => Pages\CreateServicePackage::route('/create'),
            'edit' => Pages\EditServicePackage::route('/{record}/edit'),
        ];
    }
}
