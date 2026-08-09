<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Models\Marketing\Tag;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static ?int $navigationSort = 70;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.tag.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.tag.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.tag.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.view-tags') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('marketing.manage-tags') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('marketing.manage-tags') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('marketing.manage-tags') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.tag_details'))->schema([
                TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                TextInput::make('slug')->label(__('field.slug'))->required()->unique(ignoreRecord: true)->maxLength(255),
                ColorPicker::make('color'),
                TextInput::make('description')->label(__('field.description'))->maxLength(65535),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('slug')->label(__('field.slug'))->searchable()->toggleable(),
            TextColumn::make('color')->label(__('field.color'))
                ->searchable()
                ->formatStateUsing(fn (string $state): string => "
                    <span class='inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-xs font-medium font-mono' 
                        style='background-color: {$state}20; color: {$state}; border: 1px solid {$state}40;'>
                        {$state}
                    </span>
                ")
                ->html()
                ->toggleable(),
            TextColumn::make('customers_count')->counts('customers')->label(__('field.customers')),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime('d/m/Y H:i')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
