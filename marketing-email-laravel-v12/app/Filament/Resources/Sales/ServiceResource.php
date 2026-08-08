<?php

namespace App\Filament\Resources\Sales;

use App\Enums\Sales\ServiceStatus;
use App\Filament\Resources\Sales\ServiceResource\Pages;
use App\Models\Sales\Service;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getModelLabel(): string
    {
        return __('resource.service.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.service.plural');
    }

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('sales.view-services') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('sales.manage-services') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('sales.manage-services') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('sales.manage-services') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.service_details'))->schema([
                TextInput::make('service_code')->label(__('field.service_code'))->required()->maxLength(30)->unique(ignoreRecord: true),
                TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                TextInput::make('slug')->label(__('field.slug'))->unique(ignoreRecord: true)->maxLength(255),
                Textarea::make('description')->label(__('field.description'))->rows(3),
                Textarea::make('default_scope')->label(__('field.default_scope'))->rows(3),
                Textarea::make('default_terms')->label(__('field.default_terms'))->rows(3),
                Select::make('status')
                    ->label(__('field.status'))
                    ->options(ServiceStatus::options())
                    ->default('active')
                    ->required(),
                TextInput::make('sort_order')->label(__('field.sort_order'))->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('service_code')->label(__('field.service_code'))->searchable()->sortable(),
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
            TextColumn::make('sort_order')->label(__('field.sort_order'))->sortable(),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable()->toggleable(),
        ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('action.bulk_delete'))
                        ->modalHeading(__('action.bulk_delete'))
                        ->requiresConfirmation()
                        ->visible(
                            fn (): bool => auth()->user()?->can(
                                'sales.manage-services'
                            ) ?? false
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
