<?php

namespace Dth\Commercial\Filament\Resources;

use Dth\Commercial\Enums\AudienceType;
use Dth\Commercial\Enums\BillingPeriodUnit;
use Dth\Commercial\Enums\ServiceStatus;
use Dth\Commercial\Filament\Navigation\CommercialNavigationGroup;
use Dth\Commercial\Filament\Resources\ServicePackageResource\Pages;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Models\ServicePackage;
use Dth\Commercial\Support\CommercialAuthorization;
use Dth\Commercial\Support\StatusColor;
use Dth\Commercial\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServicePackageResource extends Resource
{
    public const NAVIGATION_ICON = 'heroicon-o-gift';

    protected static ?string $model = ServicePackage::class;
    protected static string|\BackedEnum|null $navigationIcon = self::NAVIGATION_ICON;
    protected static string|\UnitEnum|null $navigationGroup = CommercialNavigationGroup::Commercial;
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'commercial-service-packages';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.packages', 'Service packages', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.package', 'Service package', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.packages', 'Service packages', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
                Section::make(UiText::get('sections.package_basic', 'Package information'))
                    ->description(UiText::get('sections.package_basic_help', 'Name the package, connect it to a service and define whether it can currently be offered.'))
                    ->schema([
                        Select::make('service_id')
                            ->label(UiText::get('models.service', 'Service'))
                            ->options(fn (): array => Service::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->helperText(UiText::get('fields.package_service_help', 'Choose the parent service this package commercializes. Only active services are available for new packages.'))
                            ->required(),
                        TextInput::make('name')
                            ->label(UiText::get('common.fields.name', 'Name'))
                            ->placeholder(UiText::get('fields.package_name_placeholder', 'Example: Professional package'))
                            ->helperText(UiText::get('fields.package_name_help', 'Use a clear customer-facing name that distinguishes this offer from other packages of the same service.'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('package_code')
                            ->label(UiText::get('fields.package_code', 'Package code'))
                            ->placeholder('PKG-PRO')
                            ->helperText(UiText::get('fields.package_code_help', 'Unique package code used by imports and integrations. Keep it stable once campaigns or opportunities reference this package.'))
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->label(UiText::get('common.fields.status', 'Status'))
                            ->options(ServiceStatus::options())
                            ->default(ServiceStatus::Active->value)
                            ->native(false)
                            ->helperText(UiText::get('fields.package_status_help', 'Active packages are available for current offers; inactive or archived packages remain visible in historical data.'))
                            ->required(),
                        Textarea::make('description')
                            ->label(UiText::get('common.fields.description', 'Description'))
                            ->placeholder(UiText::get('fields.package_description_placeholder', 'Summarize who this package is for and what it includes.'))
                            ->helperText(UiText::get('fields.package_description_help', 'Summarize the target customer and included value so Marketing and Sales can present the package consistently.'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(UiText::get('sections.package_pricing', 'Pricing'))
                    ->description(UiText::get('sections.package_pricing_help', 'Define the selling price for this package. Leave the selling price blank when the package is quoted individually.'))
                    ->schema([
                        TextInput::make('price')
                            ->label(UiText::get('fields.price', 'Selling price'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->placeholder('0')
                            ->helperText(UiText::get('fields.price_help', 'Base price for one billing cycle and the default quantity. Leave blank for contact/custom pricing.')),
                        TextInput::make('renewal_price')
                            ->label(UiText::get('fields.renewal_price', 'Renewal price'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->placeholder(UiText::get('fields.renewal_price_placeholder', 'Leave blank to use the selling price'))
                            ->helperText(UiText::get('fields.renewal_price_help', 'Optional price applied on renewal. Leave blank when renewal uses the same selling price.')),
                        TextInput::make('setup_fee')
                            ->label(UiText::get('fields.setup_fee', 'Setup fee'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->helperText(UiText::get('fields.setup_fee_help', 'One-time activation, migration or implementation fee charged in addition to the package price.')),
                        TextInput::make('currency')
                            ->label(UiText::get('fields.currency', 'Currency'))
                            ->default('VND')
                            ->maxLength(3)
                            ->minLength(3)
                            ->dehydrateStateUsing(fn ($state): string => strtoupper(trim((string) $state)))
                            ->helperText(UiText::get('fields.currency_help', 'Three-letter ISO currency code, for example VND or USD.'))
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(UiText::get('sections.package_rules', 'Audience & delivery rules'))
                    ->description(UiText::get('sections.package_rules_help', 'Set the target audience, delivery unit and default recurring period used when presenting the package.'))
                    ->schema([
                        Select::make('audience_type')
                            ->label(UiText::get('fields.audience', 'Audience'))
                            ->options(AudienceType::options())
                            ->default(AudienceType::Both->value)
                            ->native(false)
                            ->helperText(UiText::get('fields.audience_help', 'Controls whether the package is intended for individual customers, business customers or both.'))
                            ->required(),
                        TextInput::make('unit')
                            ->label(UiText::get('fields.unit', 'Unit'))
                            ->default('package')
                            ->maxLength(50)
                            ->helperText(UiText::get('fields.unit_help', 'Commercial unit shown with quantity, for example package, user, site, project or license.'))
                            ->required(),
                        TextInput::make('billing_period')
                            ->label(UiText::get('fields.billing_period', 'Billing period'))
                            ->numeric()
                            ->minValue(1)
                            ->helperText(UiText::get('fields.billing_period_help', 'Number of billing units in one recurring cycle. Leave blank when the package does not repeat.')),
                        Select::make('billing_period_unit')
                            ->label(UiText::get('fields.billing_period_unit', 'Billing unit'))
                            ->options(BillingPeriodUnit::options())
                            ->native(false)
                            ->helperText(UiText::get('fields.billing_period_unit_help', 'Choose day, month, year or one-time to explain how the billing period is interpreted.')),
                        TextInput::make('default_quantity')
                            ->label(UiText::get('fields.default_quantity', 'Default quantity'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->helperText(UiText::get('fields.default_quantity_help', 'Starting quantity proposed when this package is selected; it can still be adjusted in a specific deal.'))
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('package_code')
                    ->label(UiText::get('fields.package_code', 'Package code'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->description(fn (ServicePackage $record): ?string => filled($record->description) ? str($record->description)->limit(52)->toString() : null)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('service.name')
                    ->label(UiText::get('models.service', 'Service'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('audience_type')
                    ->label(UiText::get('fields.audience', 'Audience'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => AudienceType::options()[$state instanceof \BackedEnum ? $state->value : (string) $state] ?? (string) $state),
                TextColumn::make('price')
                    ->label(UiText::get('fields.price', 'Selling price'))
                    ->formatStateUsing(function ($state, ServicePackage $record): string {
                        if ($state === null || $state === '') {
                            return UiText::get('fields.contact_for_price', 'Contact');
                        }

                        $currency = strtoupper((string) ($record->currency ?: 'VND'));
                        $decimals = $currency === 'VND' ? 0 : 2;

                        return number_format((float) $state, $decimals, ',', '.').' '.$currency;
                    })
                    ->sortable(),
                TextColumn::make('billing_period')
                    ->label(UiText::get('fields.billing_cycle', 'Billing cycle'))
                    ->formatStateUsing(function ($state, ServicePackage $record): string {
                        if ($state === null || $record->billing_period_unit === null) {
                            return '—';
                        }

                        $unit = $record->billing_period_unit instanceof \BackedEnum
                            ? $record->billing_period_unit->value
                            : (string) $record->billing_period_unit;

                        $unitLabel = BillingPeriodUnit::options()[$unit] ?? $unit;

                        return ((int) $state).' '.$unitLabel;
                    }),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                TextColumn::make('updated_at')
                    ->label(UiText::get('fields.updated_at', 'Updated'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('service_id')
                    ->label(UiText::get('models.service', 'Service'))
                    ->options(fn (): array => Service::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(ServiceStatus::options()),
                SelectFilter::make('audience_type')
                    ->label(UiText::get('fields.audience', 'Audience'))
                    ->options(AudienceType::options()),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit'))
                        ->icon('heroicon-o-pencil-square'),
                    Actions\DeleteAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->icon('heroicon-o-trash'),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
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

    public static function canViewAny(): bool
    {
        return app(CommercialAuthorization::class)->allows('view');
    }

    public static function canCreate(): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-catalog');
    }

    public static function canEdit(Model $record): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-catalog');
    }

    public static function canDelete(Model $record): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-catalog');
    }
}
