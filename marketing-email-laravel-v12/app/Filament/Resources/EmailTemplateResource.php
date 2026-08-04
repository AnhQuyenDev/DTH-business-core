<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\Marketing\EmailTemplate;
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
use Illuminate\Support\Str;

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
        return auth()->user()?->isMarketingStaff() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isMarketingStaff() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isMarketingStaff() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
            Select::make('category')
                ->label(__('field.category'))
                ->options(static function (): array {
                    $categories = EmailTemplate::query()
                        ->whereNotNull('category')
                        ->pluck('category')
                        ->filter(fn (?string $category): bool => filled($category))
                        ->unique()
                        ->sort()
                        ->values();

                    if (! $categories->contains('marketing')) {
                        $categories->prepend('marketing');
                    }

                    return $categories->mapWithKeys(function (string $category): array {
                        $key = 'enum.email_template_category.' . $category;

                        return [
                            $category => __($key) === $key
                                ? Str::of($category)->replace('_', ' ')->headline()->toString()
                                : __($key),
                        ];
                    })->all();
                })
                ->default('marketing')
                ->required()
                ->searchable()
                ->preload(),
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
        return $table->columns([
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('category')->label(__('field.category'))->badge()
                ->formatStateUsing(function (?string $state): string {
                    if (! $state) {
                        return '';
                    }

                    $key = 'enum.email_template_category.' . $state;

                    return __($key) === $key
                        ? Str::of($state)->replace('_', ' ')->headline()->toString()
                        : __($key);
                })
                ->color(fn (?string $state): string => match ($state) {
                    'marketing' => 'info',
                    'quotation' => 'warning',
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
