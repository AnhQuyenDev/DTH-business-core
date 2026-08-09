<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactListResource\Pages;
use App\Models\Marketing\ContactList;
use App\Support\Ui\BadgePalette;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactListResource extends Resource
{
    protected static ?string $model = ContactList::class;

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('resource.contact_list.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.contact_list.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.view-lists') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('marketing.manage-lists') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('marketing.manage-lists') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('marketing.manage-lists') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.list_details'))->schema([
                TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                TextInput::make('slug')->label(__('field.slug'))->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('description')->label(__('field.description'))->maxLength(65535),
                Select::make('type')
                    ->label(__('field.type'))
                    ->options([
                        'newsletter' => __('field.list_newsletter'),
                        'service' => __('field.list_service'),
                        'event' => __('field.list_event'),
                    ])
                    ->required()
                    ->default('newsletter'),
                Select::make('status')
                    ->label(__('field.status'))
                    ->options([
                        'active' => __('field.status_active'),
                        'inactive' => __('field.status_inactive'),
                        'archived' => __('field.status_archived'),
                    ])
                    ->required()
                    ->default('active'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('slug')->label(__('field.slug'))->searchable()->toggleable(),
            TextColumn::make('type')->label(__('field.type'))->badge()
                ->color(fn ($state): string => match ($state) {
                    'newsletter', 'service', 'event' => 'info',
                    default => 'gray',
                }),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn ($state): string => match ($state) {
                    'active' => __('enum.status.active'),
                    'inactive' => __('enum.status.inactive'),
                    'archived' => __('enum.status.archived'),
                    default => $state ?? '',
                })
                ->color(fn ($state): string => BadgePalette::status($state)),
            TextColumn::make('customers_count')->counts('customers')->label(__('field.customers')),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime('d/m/Y H:i')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactLists::route('/'),
            'create' => Pages\CreateContactList::route('/create'),
            'edit' => Pages\EditContactList::route('/{record}/edit'),
        ];
    }
}
