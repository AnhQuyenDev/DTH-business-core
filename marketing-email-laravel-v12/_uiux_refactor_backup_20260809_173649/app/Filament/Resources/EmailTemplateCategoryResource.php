<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateCategoryResource\Pages;
use App\Models\Marketing\EmailTemplateCategory;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EmailTemplateCategoryResource extends Resource
{
    protected static ?string $model = EmailTemplateCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.email_template_category.plural');
    }

    public static function getModelLabel(): string
    {
        return __('resource.email_template_category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.email_template_category.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.email_template_category_details'))->schema([
                TextInput::make('name')
                    ->label(__('field.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label(__('field.slug'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('field.description'))
                    ->maxLength(65535)
                    ->rows(3),
                ColorPicker::make('color')
                    ->label(__('field.color')),
                TextInput::make('sort_order')
                    ->label(__('field.sort_order'))
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label(__('field.is_active'))
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label(__('field.name'))
                ->searchable()
                ->sortable(),
            TextColumn::make('slug')
                ->label(__('field.slug'))
                ->searchable()
                ->toggleable(),
            TextColumn::make('color')
                ->label(__('field.color'))
                ->formatStateUsing(fn (?string $state): string => $state
                    ? "<span class='inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-xs font-medium font-mono' style='background-color: {$state}20; color: {$state}; border: 1px solid {$state}40;'>{$state}</span>"
                    : ''
                )
                ->html()
                ->toggleable(),
            TextColumn::make('templates_count')
                ->counts('templates')
                ->label(__('field.email_template'))
                ->sortable(),
            TextColumn::make('sort_order')
                ->label(__('field.sort_order'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('is_active')
                ->label(__('field.is_active'))
                ->badge()
                ->formatStateUsing(fn (bool $state): string => $state ? __('field.status_active') : __('field.status_inactive'))
                ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            TextColumn::make('created_at')
                ->label(__('field.created_at'))
                ->dateTime()
                ->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplateCategories::route('/'),
            'create' => Pages\CreateEmailTemplateCategory::route('/create'),
            'edit' => Pages\EditEmailTemplateCategory::route('/{record}/edit'),
        ];
    }
}
