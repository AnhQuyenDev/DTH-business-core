<?php

namespace App\Filament\Resources;

use App\Filament\Pages\AppearanceSettingsPage;
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

    public static function getNavigationParentItem(): ?string
    {
        return AppearanceSettingsPage::getNavigationLabel();
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
                ->schema([
                    Select::make('category')
                        ->label(__('configuration.appearance.category'))
                        ->options(BadgePalette::editableCategoryOptions())
                        ->native(false)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('key', null);
                        }),

                    Select::make('key')
                        ->label(__('configuration.appearance.key'))
                        ->options(fn (Get $get): array => BadgePalette::keyOptions((string) $get('category')))
                        ->native(false)
                        ->searchable()
                        ->required()
                        ->rules([
                            fn (Get $get, ?UiBadgeStyle $record) => Rule::unique('ui_badge_styles', 'key')
                                ->where('category', (string) $get('category'))
                                ->ignore($record?->id),
                        ]),

                    ToggleButtons::make('color')
                        ->label(__('configuration.appearance.color'))
                        ->options(SystemColorPalette::options())
                        ->colors(SystemColorPalette::toggleColors())
                        ->columns([
                            'default' => 2,
                            'sm' => 4,
                            'md' => 6,
                            'xl' => 11,
                        ])
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
                    ->formatStateUsing(
                        fn (string $state, UiBadgeStyle $record): string => BadgePalette::keyOptions($record->category)[$state] ?? BadgePalette::statusLabel($state)
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('preview')
                    ->label(__('configuration.appearance.preview'))
                    ->getStateUsing(
                        fn (UiBadgeStyle $record): string => BadgePalette::keyOptions($record->category)[$record->key] ?? BadgePalette::statusLabel($record->key)
                    )
                    ->badge()
                    ->color(fn (UiBadgeStyle $record): string => SystemColorPalette::normalize($record->color)),

                TextColumn::make('color')
                    ->label(__('configuration.appearance.color'))
                    ->formatStateUsing(fn (string $state): string => SystemColorPalette::options()[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    EditAction::make()->label(__('configuration.appearance.edit')),
                    DeleteAction::make(),
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
