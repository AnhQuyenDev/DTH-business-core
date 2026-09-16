<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Models\Contact;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BusinessContactResource extends Resource
{
    protected static ?string $model = Contact::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 12;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.business_contacts', 'Business contacts', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.business_contact', 'Business contact', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.business_contacts', 'Business contacts', context: 'model');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'business');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.contact_person', 'Contact person'))
                ->schema([
                    TextInput::make('display_name')
                        ->label(UiText::get('fields.contact_person', 'Representative / contact person'))
                        ->required(),
                    TextInput::make('email')
                        ->label(UiText::get('fields.contact_email', 'Contact email'))
                        ->email(),
                    TextInput::make('phone')
                        ->label(UiText::get('fields.phone', 'Phone'))
                        ->tel(),
                    TextInput::make('source')
                        ->label(UiText::get('common.fields.source', 'Source')),
                ])
                ->columns(2),
            Section::make(UiText::get('sections.company', 'Company'))
                ->schema([
                    TextInput::make('profile.company_name')
                        ->label(UiText::get('fields.company_name', 'Company name'))
                        ->required(),
                    TextInput::make('profile.tax_code')
                        ->label(UiText::get('fields.tax_code', 'Tax code')),
                    TextInput::make('profile.company_address')
                        ->label(UiText::get('fields.address', 'Address')),
                    TextInput::make('profile.legal_representative')
                        ->label(UiText::get('fields.legal_representative', 'Legal representative')),
                    TextInput::make('profile.contact_position')
                        ->label(UiText::get('fields.position', 'Position')),
                    TextInput::make('profile.business_email')
                        ->label(UiText::get('fields.business_email', 'Business email'))
                        ->email(),
                    TextInput::make('profile.business_phone')
                        ->label(UiText::get('fields.business_phone', 'Business phone'))
                        ->tel(),
                    TextInput::make('profile.industry')
                        ->label(UiText::get('fields.industry', 'Industry')),
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
                TextColumn::make('businessProfile.company_name')
                    ->label(UiText::get('fields.company_name', 'Company'))
                    ->searchable(),
                TextColumn::make('display_name')
                    ->label(UiText::get('fields.contact_person', 'Contact person'))
                    ->searchable(),
                TextColumn::make('businessProfile.tax_code')
                    ->label(UiText::get('fields.tax_code_short', 'Tax code'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(UiText::get('common.fields.email', 'Email'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(UiText::get('fields.phone', 'Phone'))
                    ->searchable(),
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
            'index' => BusinessContactResource\Pages\ListBusinessContacts::route('/'),
            'create' => BusinessContactResource\Pages\CreateBusinessContact::route('/create'),
            'edit' => BusinessContactResource\Pages\EditBusinessContact::route('/{record}/edit'),
        ];
    }
}
