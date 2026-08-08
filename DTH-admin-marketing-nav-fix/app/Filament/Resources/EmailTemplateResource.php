<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\EmailTemplateCategory;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.email_marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.email_template.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.email_template.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.email_template.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.view-templates') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('marketing.manage-templates') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('marketing.manage-templates') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
            Select::make('category_id')
                ->label(__('field.category'))
                ->relationship('categoryRelation', 'name')
                ->searchable()
                ->preload()
                ->default(fn () => EmailTemplateCategory::where('slug', 'marketing')->first()?->id)
                ->required()
                ->createOptionForm([
                    TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                    TextInput::make('slug')->label(__('field.slug'))->required()->unique(ignoreRecord: true)->maxLength(255),
                ]),
            Grid::make(3)->schema([
                TextInput::make('subject')->label(__('field.subject'))->required()->maxLength(255),
                TextInput::make('preheader')->label(__('field.preheader'))->maxLength(255),
                Select::make('status')->label(__('field.status'))->options([
                    'active' => __('field.status_active'),
                    'inactive' => __('field.status_inactive'),
                    'draft' => __('field.status_draft'),
                ])->default('draft')->required(),
            ]),
            RichEditor::make('html_body')->label(__('field.html_body'))->required()->columnSpanFull(),
            Textarea::make('text_body')->label(__('field.text_body'))->rows(6)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('categoryRelation'))
            ->columns([
                TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
                TextColumn::make('categoryRelation.name')->label(__('field.category'))
                    ->badge()
                    ->color(fn (EmailTemplate $record): string => match (true) {
                        $record->categoryRelation?->slug === 'marketing' => 'info',
                        $record->categoryRelation?->slug === 'quotation' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('subject')->label(__('field.subject'))->searchable()->limit(50),
                TextColumn::make('status')->label(__('field.status'))->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __('field.status_'.$state) : '')
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable(),
            ])
            ->actions([ActionGroup::make([
                Action::make('preview')
                    ->label(__('action.preview'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (EmailTemplate $record): string => route('marketing.email-templates.preview', $record), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
