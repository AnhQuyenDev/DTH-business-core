<?php

namespace Dth\Marketing\Filament\Resources;

use Dth\Marketing\Filament\Navigation\MarketingNavigationGroup;
use Dth\Marketing\Filament\Resources\ContactListResource\Pages;
use Dth\Marketing\Filament\Resources\ContactListResource\RelationManagers\MembersRelationManager;
use Dth\Marketing\Filament\Support\StatusColor;
use Dth\Marketing\Models\ContactList;
use Dth\Marketing\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactListResource extends Resource
{
    protected static ?string $model = ContactList::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\UnitEnum|null $navigationGroup = MarketingNavigationGroup::Marketing;
    protected static ?int $navigationSort = 50;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.contact_lists', 'Marketing Lists', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.contact_list', 'Marketing List', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.contact_lists', 'Marketing Lists', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('audience.list', 'Marketing List'))
                ->icon('heroicon-o-rectangle-stack')
                ->schema([
                    TextInput::make('name')
                        ->label(UiText::get('common.fields.name', 'Name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label(UiText::get('audience.slug', 'Slug'))
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('type')
                        ->label(UiText::get('audience.type', 'Type'))
                        ->options([
                            'newsletter' => UiText::get('audience.type_newsletter', 'Newsletter'),
                            'service' => UiText::get('audience.type_service', 'Service'),
                            'campaign' => UiText::get('audience.type_campaign', 'Campaign'),
                            'manual' => UiText::get('audience.type_manual', 'Manual'),
                        ])
                        ->default('newsletter')
                        ->required()
                        ->native(false),
                    Select::make('status')
                        ->label(UiText::get('common.fields.status', 'Status'))
                        ->options([
                            'active' => UiText::get('common.status.active', 'Active'),
                            'archived' => UiText::get('common.status.archived', 'Archived'),
                        ])
                        ->default('active')
                        ->required()
                        ->native(false),
                    Textarea::make('description')
                        ->label(UiText::get('common.fields.description', 'Description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(UiText::get('audience.type', 'Type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                TextColumn::make('members_count')
                    ->counts('members')
                    ->label(UiText::get('audience.members', 'Members'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(UiText::get('common.fields.created_at', 'Created'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options([
                        'active' => UiText::get('common.status.active', 'Active'),
                        'archived' => UiText::get('common.status.archived', 'Archived'),
                    ]),
            ])
            ->filtersFormColumns(1)
            ->deferFilters(false)
            ->hiddenFilterIndicators()
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit'))
                        ->icon('heroicon-o-pencil-square'),
                    Actions\DeleteAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->icon('heroicon-o-trash'),
            
                ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('activate')
                        ->label(UiText::get('common.actions.activate', 'Activate'))
                        ->visible(fn (): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()))
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => ContactList::query()->whereKey($records->modelKeys())->update(['status' => 'active'])),
                    Actions\BulkAction::make('archive')
                        ->label(UiText::get('common.actions.archive', 'Archive'))
                        ->visible(fn (): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()))
                        ->icon('heroicon-o-archive-box')
                        ->action(fn ($records) => ContactList::query()->whereKey($records->modelKeys())->update(['status' => 'archived'])),
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete')),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
        ];
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
