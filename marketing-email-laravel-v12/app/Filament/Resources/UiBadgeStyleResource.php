<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UiBadgeStyleResource\Pages;
use App\Models\System\UiBadgeStyle;
use App\Support\Ui\BadgePalette;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class UiBadgeStyleResource extends Resource
{
    protected static ?string $model = UiBadgeStyle::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?int $navigationSort = 80;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.badge_styles');
    }

    public static function getModelLabel(): string
    {
        return __('resource.ui_badge_style.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.ui_badge_style.plural');
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('category')
                    ->label(__('field.badge_category'))
                    ->options(BadgePalette::categoryOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('key', null)),
                Select::make('key')
                    ->label(__('field.badge_key'))
                    ->options(fn (Get $get): array => BadgePalette::keyOptions((string) $get('category')))
                    ->searchable()
                    ->required()
                    ->rules([
                        fn (Get $get, ?UiBadgeStyle $record) => Rule::unique('ui_badge_styles', 'key')
                            ->where('category', (string) $get('category'))
                            ->ignore($record?->id),
                    ]),
                Select::make('color')
                    ->label(__('field.color'))
                    ->options(BadgePalette::colorOptions())
                    ->required()
                    ->native(false),
            ])
            ->columns(['default' => 1, 'md' => 2, 'xl' => 3]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->label(__('field.badge_category'))
                    ->formatStateUsing(fn (string $state): string => BadgePalette::categoryOptions()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('key')
                    ->label(__('field.badge_key'))
                    ->formatStateUsing(fn (string $state, UiBadgeStyle $record): string => BadgePalette::keyOptions($record->category)[$state] ?? $state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('color')
                    ->label(__('field.color'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => BadgePalette::colorOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => $state),
                TextColumn::make('updated_at')
                    ->label(__('field.updated_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('category')
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
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
