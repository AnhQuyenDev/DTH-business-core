<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UiBadgeStyleResource\Pages;
use App\Models\System\UiBadgeStyle;
use App\Support\Ui\BadgePalette;
use App\Support\Ui\SystemColorPalette;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class UiBadgeStyleResource extends Resource
{
    protected static ?string $model = UiBadgeStyle::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?int $navigationSort = 41;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.appearance.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('configuration.appearance.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('configuration.appearance.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('system.manage-company-settings') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('system.manage-company-settings') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('system.manage-company-settings') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('system.manage-company-settings') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('category', '!=', 'status');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('configuration.appearance.plural'))
                ->icon('heroicon-o-swatch')
                ->iconColor('primary')
                ->compact()
                ->extraAttributes(['class' => 'dth-config-form'])
                ->schema([
                    Select::make('category')
                        ->label(__('configuration.appearance.category'))
                        ->options(BadgePalette::editableCategoryOptions())
                        ->native(false)
                        ->required()
                        ->live()
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.appearance.helper_category'))
                        ->afterStateUpdated(fn (Set $set) => $set('key', null)),

                    Select::make('key')
                        ->label(__('configuration.appearance.key'))
                        ->options(fn (Get $get): array => BadgePalette::keyOptions((string) $get('category')))
                        ->native(false)
                        ->searchable()
                        ->required()
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.appearance.helper_key'))
                        ->rules([
                            fn (Get $get, ?UiBadgeStyle $record) => Rule::unique('ui_badge_styles', 'key')
                                ->where('category', (string) $get('category'))
                                ->ignore($record?->id),
                        ]),

                    ToggleButtons::make('color')
                        ->label(__('configuration.appearance.color'))
                        ->options(SystemColorPalette::options())
                        ->colors(SystemColorPalette::toggleColors())
                        ->hiddenButtonLabels()
                        ->inline()
                        ->extraAttributes(['class' => 'dth-color-swatch-picker'])
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.appearance.helper_color'))
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->label(__('configuration.appearance.category'))
                    ->formatStateUsing(fn (string $state): string => BadgePalette::editableCategoryOptions()[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('key')
                    ->label(__('configuration.appearance.key'))
                    ->formatStateUsing(fn (string $state, UiBadgeStyle $record): string => BadgePalette::keyOptions($record->category)[$state] ?? BadgePalette::statusLabel($state))
                    ->searchable()
                    ->sortable(),

                IconColumn::make('color')
                    ->label(__('configuration.appearance.color'))
                    ->icon('app-circle')
                    ->color(fn (UiBadgeStyle $record): string => SystemColorPalette::normalize($record->color))
                    ->tooltip(fn (UiBadgeStyle $record): string => SystemColorPalette::options()[$record->color] ?? $record->color),

                TextColumn::make('preview')
                    ->label(__('configuration.appearance.preview'))
                    ->getStateUsing(fn (UiBadgeStyle $record): string => BadgePalette::keyOptions($record->category)[$record->key] ?? BadgePalette::statusLabel($record->key))
                    ->badge()
                    ->color(fn (UiBadgeStyle $record): string => SystemColorPalette::normalize($record->color)),

                TextColumn::make('updated_at')
                    ->label(__('configuration.appearance.updated_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('category')
            ->filters([
                SelectFilter::make('category')
                    ->label(__('configuration.appearance.category'))
                    ->options(BadgePalette::editableCategoryOptions()),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()->label(__('configuration.appearance.edit'))->icon('heroicon-o-pencil-square'),
                    DeleteAction::make()->label(__('configuration.common.delete'))->icon('heroicon-o-trash'),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUiBadgeStyles::route('/'),
            'create' => Pages\CreateUiBadgeStyle::route('/create'),
            'edit' => Pages\EditUiBadgeStyle::route('/{record}/edit'),
        ];
    }
}
