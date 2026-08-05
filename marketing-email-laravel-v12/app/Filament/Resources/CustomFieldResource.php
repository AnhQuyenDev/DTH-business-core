<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomFieldResource\Pages;
use App\Models\Marketing\CustomField;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomFieldResource extends Resource
{
    protected static ?string $model = CustomField::class;

    protected static ?int $navigationSort = 999;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('resource.custom_field.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.custom_field.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.view-custom-fields') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('marketing.manage-custom-fields') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('marketing.manage-custom-fields') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('marketing.manage-custom-fields') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.custom_field_details'))->schema([
                TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                TextInput::make('key')->required()->unique(ignoreRecord: true)->maxLength(255),
                Select::make('type')
                    ->required()
                    ->options([
                        'text' => __('field.field_type_text'),
                        'number' => __('field.field_type_number'),
                        'date' => __('field.field_type_date'),
                        'boolean' => __('field.field_type_boolean'),
                        'select' => __('field.field_type_select'),
                        'multi_select' => __('field.field_type_multi_select'),
                    ]),
                KeyValue::make('options')
                    ->keyLabel(__('field.option_value'))
                    ->valueLabel(__('field.option_label'))
                    ->nullable(),
                Select::make('is_required')
                    ->required()
                    ->options([
                        0 => __('field.no'),
                        1 => __('field.yes'),
                    ])
                    ->default(0),
                Select::make('is_filterable')
                    ->required()
                    ->options([
                        0 => __('field.no'),
                        1 => __('field.yes'),
                    ])
                    ->default(0),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('key')->label(__('field.key'))->searchable()->toggleable(),
            TextColumn::make('type')->label(__('field.type'))->badge()
                ->color(fn ($state): string => match ($state) {
                    'text', 'number', 'date', 'boolean', 'select', 'multi_select' => 'info',
                    default => 'gray',
                }),
            TextColumn::make('is_required')->label(__('field.is_required'))->badge()
                ->formatStateUsing(fn ($state): string => (string) $state === '1' ? __('field.yes') : __('field.no'))
                ->color(fn ($state): string => (string) $state === '1' ? 'success' : 'gray'),
            TextColumn::make('is_filterable')->label(__('field.is_filterable'))->badge()
                ->formatStateUsing(fn ($state): string => (string) $state === '1' ? __('field.yes') : __('field.no'))
                ->color(fn ($state): string => (string) $state === '1' ? 'info' : 'gray'),
            TextColumn::make('sort_order')->label(__('field.sort_order'))->sortable(),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomFields::route('/'),
            'create' => Pages\CreateCustomField::route('/create'),
            'edit' => Pages\EditCustomField::route('/{record}/edit'),
        ];
    }
}
