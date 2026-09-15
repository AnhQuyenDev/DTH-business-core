<?php

namespace Dth\HumanResource\Filament\Resources;

use Dth\HumanResource\Enums\BusinessFunction;
use Dth\HumanResource\Enums\PositionAuthority;
use Dth\HumanResource\Enums\PositionGroup;
use Dth\HumanResource\Filament\Navigation\HumanResourceNavigationGroup;
use Dth\HumanResource\Filament\Resources\PositionResource\Pages;
use Dth\HumanResource\Models\Position;
use Dth\HumanResource\Support\StatusColor;
use Dth\HumanResource\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';
    protected static string|\UnitEnum|null $navigationGroup = HumanResourceNavigationGroup::HumanResource;
    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.positions', 'Job titles', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.position', 'Job title', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.position_plural', 'Job titles', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.position', 'Job title'))
                ->schema([
                    TextInput::make('code')
                        ->label(UiText::get('fields.position_code', 'Job-title code'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Position $record): bool => $record !== null),
                    TextInput::make('title')
                        ->label(UiText::get('fields.position', 'Job title'))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('group_key')
                        ->label(UiText::get('fields.position_group', 'Job-title group'))
                        ->options(PositionGroup::options())
                        ->default(PositionGroup::Professional->value)
                        ->required()
                        ->native(false),
                    Select::make('authority_level')
                        ->label(UiText::get('fields.authority_level', 'Authority level'))
                        ->options(PositionAuthority::options())
                        ->default(PositionAuthority::Member->value)
                        ->required()
                        ->native(false),
                    Select::make('function_key')
                        ->label(UiText::get('fields.business_function', 'Business function'))
                        ->options(BusinessFunction::options(includeSystem: false))
                        ->placeholder(UiText::get('positions.all_functions', 'All business functions'))
                        ->searchable()
                        ->native(false),
                    TextInput::make('sort_order')
                        ->label(UiText::get('fields.sort_order', 'Sort order'))
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                    Toggle::make('is_active')
                        ->label(UiText::get('common.status.active', 'Active'))
                        ->default(true),
                    Textarea::make('description')
                        ->label(UiText::get('common.fields.description', 'Description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(UiText::get('fields.position', 'Job title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('group_key')
                    ->label(UiText::get('fields.position_group', 'Group'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof PositionGroup ? $state : PositionGroup::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->color(fn ($state): string => StatusColor::positionGroup($state)),
                TextColumn::make('authority_level')
                    ->label(UiText::get('fields.authority_level', 'Authority'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof PositionAuthority ? $state : PositionAuthority::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->color(fn ($state): string => StatusColor::authority($state)),
                TextColumn::make('function_key')
                    ->label(UiText::get('fields.business_function', 'Business function'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof BusinessFunction ? $state : BusinessFunction::tryFrom((string) $state))?->label() ?? UiText::get('positions.all_functions', 'All functions'))
                    ->color(fn ($state): string => StatusColor::businessFunction($state)),
                TextColumn::make('employees_count')
                    ->label(UiText::get('fields.employee_count', 'Employees'))
                    ->counts('employees')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(UiText::get('common.status.active', 'Active'))
                    ->boolean(),
                TextColumn::make('code')
                    ->label(UiText::get('fields.position_code', 'Code'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group_key')
                    ->label(UiText::get('fields.position_group', 'Group'))
                    ->options(PositionGroup::options()),
                SelectFilter::make('authority_level')
                    ->label(UiText::get('fields.authority_level', 'Authority'))
                    ->options(PositionAuthority::options()),
                TernaryFilter::make('is_active')
                    ->label(UiText::get('common.status.active', 'Active')),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && ! $record->employees()->exists();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPositions::route('/'),
            'create' => Pages\CreatePosition::route('/create'),
            'edit' => Pages\EditPosition::route('/{record}/edit'),
        ];
    }
}
