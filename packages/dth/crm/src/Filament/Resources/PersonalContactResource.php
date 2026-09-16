<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Models\Contact;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PersonalContactResource extends Resource
{
    protected static ?string $model = Contact::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 11;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.personal_contacts', 'Personal contacts', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.personal_contact', 'Personal contact', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.personal_contacts', 'Personal contacts', context: 'model');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'personal');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.personal_contact', 'Personal contact'))
                ->schema([
                    TextInput::make('display_name')
                        ->label(UiText::get('fields.full_name', 'Full name'))
                        ->required(),
                    TextInput::make('email')
                        ->label(UiText::get('common.fields.email', 'Email'))
                        ->email(),
                    TextInput::make('phone')
                        ->label(UiText::get('fields.phone', 'Phone'))
                        ->tel(),
                    TextInput::make('source')
                        ->label(UiText::get('common.fields.source', 'Source')),
                    TextInput::make('profile.occupation')
                        ->label(UiText::get('fields.occupation', 'Occupation')),
                    DatePicker::make('profile.date_of_birth')
                        ->label(UiText::get('fields.date_of_birth', 'Date of birth')),
                    Select::make('profile.gender')
                        ->label(UiText::get('fields.gender', 'Gender'))
                        ->options(CrmOptions::genders())
                        ->native(false),
                    TextInput::make('profile.province')
                        ->label(UiText::get('fields.province', 'Province / city')),
                    TextInput::make('profile.service_interest')
                        ->label(UiText::get('fields.service_interest', 'Service interest')),
                    TextInput::make('profile.expected_budget')
                        ->label(UiText::get('fields.expected_budget', 'Expected budget'))
                        ->numeric(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contact_code')
                    ->label(UiText::get('fields.contact_code_short', 'Code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label(UiText::get('fields.full_name', 'Full name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(UiText::get('common.fields.email', 'Email'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(UiText::get('fields.phone', 'Phone'))
                    ->searchable(),
                TextColumn::make('personalProfile.occupation')
                    ->label(UiText::get('fields.occupation', 'Occupation')),
                TextColumn::make('source')
                    ->label(UiText::get('common.fields.source', 'Source')),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->icon('heroicon-o-pencil-square')
                        ->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()->icon('heroicon-o-trash')
                        ->label(UiText::get('common.actions.delete', 'Delete')),
                ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->icon('heroicon-o-trash')
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => PersonalContactResource\Pages\ListPersonalContacts::route('/'),
            'create' => PersonalContactResource\Pages\CreatePersonalContact::route('/create'),
            'edit' => PersonalContactResource\Pages\EditPersonalContact::route('/{record}/edit'),
        ];
    }
}
