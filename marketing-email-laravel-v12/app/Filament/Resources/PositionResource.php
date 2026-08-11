<?php

namespace App\Filament\Resources;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\PositionGroup;
use App\Filament\Pages\OrganizationAccessPage;
use App\Filament\Resources\PositionResource\Pages;
use App\Models\Crm\Position;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 22;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationParentItem(): ?string
    {
        return OrganizationAccessPage::getNavigationLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.navigation.positions');
    }

    public static function getModelLabel(): string
    {
        return __('configuration.position.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('configuration.position.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return (auth()->user()?->can('system.manage-organization') ?? false)
            && $record instanceof Position
            && ! $record->staff()->exists();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('configuration.position.section'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('configuration.position.title'))
                        ->datalist(Position::titleSuggestions())
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            if (filled($get('code')) || blank($state)) {
                                return;
                            }

                            $set('code', Position::suggestedCode($state));
                        }),

                    TextInput::make('code')
                        ->label(__('configuration.position.code'))
                        ->required()
                        ->maxLength(100)
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Position $record): bool => $record !== null)
                        ->dehydrated()
                        ->helperText(__('configuration.position.code_helper')),

                    Select::make('group_key')
                        ->label(__('configuration.position.group'))
                        ->options(PositionGroup::options())
                        ->default(PositionGroup::Professional->value)
                        ->native(false)
                        ->required(),

                    Select::make('authority_level')
                        ->label(__('configuration.position.authority'))
                        ->options(PositionAuthority::options())
                        ->native(false)
                        ->default(PositionAuthority::Member->value)
                        ->required(),

                    Select::make('function_key')
                        ->label(__('configuration.position.function'))
                        ->options(collect(DepartmentFunction::options())->except(['admin', 'other'])->all())
                        ->placeholder(__('configuration.position.function_all'))
                        ->native(false)
                        ->searchable(),

                    Toggle::make('is_active')
                        ->label(__('configuration.position.active'))
                        ->default(true),

                    Textarea::make('description')
                        ->label(__('configuration.position.description'))
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('configuration.position.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('group_key')
                    ->label(__('configuration.position.group'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof PositionGroup
                        ? $state
                        : PositionGroup::tryFrom((string) $state))?->label() ?? __('common.not_available'))
                    ->color(fn ($state): string => ($state instanceof PositionGroup
                        ? $state
                        : PositionGroup::tryFrom((string) $state))?->color() ?? 'gray'),

                TextColumn::make('authority_level')
                    ->label(__('configuration.position.authority'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof PositionAuthority
                        ? $state
                        : PositionAuthority::tryFrom((string) $state))?->label() ?? __('common.not_available'))
                    ->color(fn ($state): string => match ($state instanceof PositionAuthority
                        ? $state
                        : PositionAuthority::tryFrom((string) $state)) {
                        PositionAuthority::Executive => 'danger',
                        PositionAuthority::Manager => 'warning',
                        PositionAuthority::Lead => 'info',
                        PositionAuthority::Member => 'success',
                        PositionAuthority::Limited => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('function_key')
                    ->label(__('configuration.position.function'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof DepartmentFunction
                        ? $state
                        : DepartmentFunction::tryFrom((string) $state))?->label() ?? __('configuration.position.function_all'))
                    ->color(fn ($state): string => ($state instanceof DepartmentFunction
                        ? $state
                        : DepartmentFunction::tryFrom((string) $state))?->color() ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('staff_count')
                    ->label(__('configuration.position.staff_count'))
                    ->counts('staff')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('configuration.position.active'))
                    ->boolean(),

                TextColumn::make('code')
                    ->label(__('configuration.position.code'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                ActionGroup::make([
                    EditAction::make()->label(__('configuration.position.edit')),
                    DeleteAction::make()
                        ->label(__('configuration.position.delete'))
                        ->visible(fn (Position $record): bool => static::canDelete($record)),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->filters([
                SelectFilter::make('group_key')
                    ->label(__('configuration.position.group'))
                    ->options(PositionGroup::options()),
                SelectFilter::make('authority_level')
                    ->label(__('configuration.position.authority'))
                    ->options(PositionAuthority::options()),
                SelectFilter::make('function_key')
                    ->label(__('configuration.position.function'))
                    ->options(DepartmentFunction::options()),
            ]);
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
