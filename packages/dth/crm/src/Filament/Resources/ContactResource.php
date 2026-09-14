<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Enums\ContactType;
use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\ContactResource\Pages;
use Dth\Crm\Models\Contact;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\StatusColor;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.contacts', 'Contacts', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.contact', 'Contact', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.contacts', 'Contacts', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.contact', 'Contact'))
                ->schema([
                    TextInput::make('contact_code')
                        ->label(UiText::get('fields.contact_code', 'Contact code')),
                    Select::make('type')
                        ->label(UiText::get('fields.contact_type', 'Contact type'))
                        ->options(ContactType::options())
                        ->native(false)
                        ->required(),
                    TextInput::make('display_name')
                        ->label(UiText::get('fields.display_name', 'Display name'))
                        ->required(),
                    TextInput::make('email')
                        ->label(UiText::get('common.fields.email', 'Email'))
                        ->email(),
                    TextInput::make('phone')
                        ->label(UiText::get('fields.phone', 'Phone'))
                        ->tel(),
                    TextInput::make('source')
                        ->label(UiText::get('common.fields.source', 'Source')),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contact_code')
                    ->label(UiText::get('fields.contact_code', 'Contact code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(UiText::get('fields.contact_type', 'Contact type'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('contact_type', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'contact_type')),
                TextColumn::make('display_name')
                    ->label(UiText::get('fields.display_name', 'Display name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(UiText::get('common.fields.email', 'Email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(UiText::get('fields.phone', 'Phone'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source')
                    ->label(UiText::get('common.fields.source', 'Source'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(UiText::get('fields.contact_type', 'Contact type'))
                    ->options(ContactType::options()),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()
                        ->label(UiText::get('common.actions.view', 'View')),
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete')),
                ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
