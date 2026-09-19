<?php

namespace Dth\Commercial\Filament\Resources;

use Dth\Commercial\Enums\ServiceStatus;
use Dth\Commercial\Filament\Navigation\CommercialNavigationGroup;
use Dth\Commercial\Filament\Resources\ServiceResource\Pages;
use Dth\Commercial\Models\Service;
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

class ServiceResource extends Resource
{
    public const NAVIGATION_ICON = 'heroicon-o-rectangle-stack';

    protected static ?string $model = Service::class;
    protected static string|\BackedEnum|null $navigationIcon = self::NAVIGATION_ICON;
    protected static string|\UnitEnum|null $navigationGroup = CommercialNavigationGroup::Commercial;
    protected static ?int $navigationSort = 10;
    protected static ?string $slug = 'commercial-services';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.services', 'Service catalog', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.service', 'Service', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.services', 'Services', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
                Section::make(UiText::get('sections.service_basic', 'Basic information'))
                    ->description(UiText::get('sections.service_basic_help', 'Define the identity and availability of the service in the shared commercial catalog.'))
                    ->schema([
                        TextInput::make('name')
                            ->label(UiText::get('common.fields.name', 'Name'))
                            ->placeholder(UiText::get('fields.service_name_placeholder', 'Example: CRM implementation'))
                            ->helperText(UiText::get('fields.service_name_help', 'Use the customer-facing service name shared by Marketing, Email and business opportunities.'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('service_code')
                            ->label(UiText::get('fields.service_code', 'Service code'))
                            ->placeholder('SVC-CRM')
                            ->helperText(UiText::get('fields.service_code_help', 'Unique business code used for import, reporting and cross-module references. Avoid changing it after the service is in use.'))
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        TextInput::make('slug')
                            ->label(UiText::get('fields.reference', 'Reference'))
                            ->helperText(UiText::get('fields.reference_help', 'Stable reference used across modules. Leave blank to generate from the service name.'))
                            ->placeholder('crm-implementation')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->label(UiText::get('common.fields.status', 'Status'))
                            ->options(ServiceStatus::options())
                            ->default(ServiceStatus::Active->value)
                            ->helperText(UiText::get('fields.service_status_help', 'Active services can own products and be reused by Marketing; inactive or archived records remain available for history.'))
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(UiText::get('sections.service_content', 'Description & offer defaults'))
                    ->description(UiText::get('sections.service_content_help', 'Content here becomes the default business context reused when products from this service are presented in campaigns and commercial offers.'))
                    ->schema([
                        Textarea::make('description')
                            ->label(UiText::get('common.fields.description', 'Description'))
                            ->placeholder(UiText::get('fields.service_description_placeholder', 'Describe the customer problem, value proposition and expected outcome.'))
                            ->helperText(UiText::get('fields.service_description_help', 'Keep this concise and reusable because other modules may surface it as the default offer context.'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('default_scope')
                            ->label(UiText::get('fields.scope', 'Default scope'))
                            ->placeholder(UiText::get('fields.scope_placeholder', 'Main implementation scope, deliverables and boundaries.'))
                            ->helperText(UiText::get('fields.scope_help', 'Describe the default deliverables, boundaries and exclusions. Packages may refine this scope later.'))
                            ->rows(4),
                        Textarea::make('default_terms')
                            ->label(UiText::get('fields.terms', 'Default terms'))
                            ->placeholder(UiText::get('fields.terms_placeholder', 'Default commercial terms or notes applied to this service.'))
                            ->helperText(UiText::get('fields.terms_help', 'Record reusable commercial assumptions or notes; do not put deal-specific contractual terms here.'))
                            ->rows(4),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('service_code')
                    ->label(UiText::get('fields.service_code', 'Service code'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->description(fn (Service $record): ?string => filled($record->description) ? str($record->description)->limit(58)->toString() : null)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('slug')
                    ->label(UiText::get('fields.reference', 'Reference'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label(UiText::get('models.products', 'Products'))
                    ->alignCenter()
                    ->sortable(),
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
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(ServiceStatus::options()),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
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
