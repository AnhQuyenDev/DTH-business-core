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
    protected static ?string $model = ServicePackage::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';
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
                            ->required(),
                        TextInput::make('name')
                            ->label(UiText::get('common.fields.name', 'Name'))
                            ->placeholder(UiText::get('fields.package_name_placeholder', 'Example: Professional package'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('package_code')
                            ->label(UiText::get('fields.package_code', 'Package code'))
                            ->placeholder('PKG-PRO')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->label(UiText::get('common.fields.status', 'Status'))
                            ->options(ServiceStatus::options())
                            ->default(ServiceStatus::Active->value)
                            ->native(false)
                            ->required(),
                        Textarea::make('description')
                            ->label(UiText::get('common.fields.description', 'Description'))
                            ->placeholder(UiText::get('fields.package_description_placeholder', 'Summarize who this package is for and what it includes.'))
                            ->rows(3)
                            ->columnSpanFull(),
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
                            ->required(),
                        TextInput::make('unit')
                            ->label(UiText::get('fields.unit', 'Unit'))
                            ->default('package')
                            ->maxLength(50)
                            ->required(),
                        TextInput::make('billing_period')
                            ->label(UiText::get('fields.billing_period', 'Billing period'))
                            ->numeric()
                            ->minValue(1),
                        Select::make('billing_period_unit')
                            ->label(UiText::get('fields.billing_period_unit', 'Billing unit'))
                            ->options(BillingPeriodUnit::options())
                            ->native(false),
                        TextInput::make('default_quantity')
                            ->label(UiText::get('fields.default_quantity', 'Default quantity'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
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
