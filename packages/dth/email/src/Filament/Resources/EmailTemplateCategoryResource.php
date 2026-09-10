<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\EmailTemplateCategoryResource\Pages;
use Dth\Email\Models\EmailTemplateCategory;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplateCategoryResource extends Resource
{
    protected static ?string $model = EmailTemplateCategory::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';
    protected static string|\UnitEnum|null $navigationGroup = EmailNavigationGroup::Email;
    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.template_categories', 'Template Categories', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.template_category', 'Template Category', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.template_categories', 'Template Categories', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make(UiText::get('template_category.category', 'Category'))
                    ->icon('heroicon-o-folder')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(UiText::get('common.fields.name', 'Name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 8]),
                        Forms\Components\Toggle::make('is_active')
                            ->label(UiText::get('template_category.active', 'Active'))
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\Textarea::make('description')
                            ->label(UiText::get('template_category.description', 'Description'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 12])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(UiText::get('template_category.system_slug', 'System slug'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('templates_count')
                    ->counts('templates')
                    ->label(UiText::get('template_category.templates', 'Templates')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(UiText::get('template_category.active', 'Active')),
            ])
            ->defaultSort('name')
            ->actions([
                Actions\EditAction::make()
                    ->label(UiText::get('common.actions.edit', 'Edit'))
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
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
