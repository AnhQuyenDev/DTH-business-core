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
use Illuminate\Support\HtmlString;

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
                        ->label(new HtmlString(
                            self::colorPickerStyles()
                            .e(__('configuration.appearance.color'))
                        ))
                        ->options(
                            collect(SystemColorPalette::options())
                                ->mapWithKeys(fn (string $label, string $color): array => [
                                    $color => new HtmlString(sprintf(
                                        '<span
                                            class="dth-shared-color-swatch"
                                            title="%s"
                                            aria-hidden="true"
                                            style="--dth-swatch:%s;background-color:%s;"
                                        ></span>
                                        <span class="sr-only">%s</span>',
                                        e($label),
                                        e(SystemColorPalette::hex($color)),
                                        e(SystemColorPalette::hex($color)),
                                        e($label),
                                    )),
                                ])
                                ->all()
                        )
                        ->inline()
                        ->extraAttributes(['class' => 'dth-color-swatch-picker'])
                        ->hintIcon(
                            'heroicon-m-question-mark-circle',
                            __('configuration.appearance.helper_color')
                        )
                        ->default(SystemColorPalette::DEFAULT)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    private static function colorPickerStyles(): string
    {
        return <<<'HTML'
    <style>
        .dth-color-swatch-picker.fi-fo-toggle-buttons,
        .dth-color-swatch-picker .fi-fo-toggle-buttons {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: .72rem !important;
        }

        .dth-color-swatch-picker > div,
        .dth-color-swatch-picker .fi-fo-toggle-buttons > div {
            padding: 0 !important;
            margin: 0 !important;
        }

        .dth-color-swatch-picker label.fi-btn {
            width: 2rem !important;
            min-width: 2rem !important;
            height: 2rem !important;
            min-height: 2rem !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 9999px !important;
            background: transparent !important;
            box-shadow: none !important;
            outline: none !important;
            overflow: visible !important;
        }

        .dth-color-swatch-picker label.fi-btn:hover,
        .dth-color-swatch-picker label.fi-btn:focus-visible {
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        .dth-color-swatch-picker .dth-shared-color-swatch {
            display: block;
            width: 1.72rem;
            height: 1.72rem;
            border-radius: 9999px;
            border: 2px solid color-mix(
                in srgb,
                var(--dth-swatch) 72%,
                #111827 28%
            );
            box-shadow:
                inset 0 0 0 2px color-mix(
                    in srgb,
                    var(--dth-swatch) 88%,
                    white 12%
                ),
                0 0 0 1px rgba(255, 255, 255, .08);
            box-sizing: border-box;
            transition:
                box-shadow .14s ease,
                transform .14s ease,
                filter .14s ease;
        }

        .dth-color-swatch-picker label.fi-btn:hover
            .dth-shared-color-swatch,
        .dth-color-swatch-picker label.fi-btn:focus-visible
            .dth-shared-color-swatch {
            transform: scale(1.06);
            filter: saturate(1.06) brightness(1.04);
        }

        .dth-color-swatch-picker input:checked
            + label.fi-btn
            .dth-shared-color-swatch {
            transform: scale(1.06);
            box-shadow:
                inset 0 0 0 2px color-mix(
                    in srgb,
                    var(--dth-swatch) 88%,
                    white 12%
                ),
                0 0 0 2px #ffffff,
                0 0 0 4px var(--dth-swatch),
                0 0 12px color-mix(
                    in srgb,
                    var(--dth-swatch) 72%,
                    transparent
                );
        }
    </style>
    HTML;
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
