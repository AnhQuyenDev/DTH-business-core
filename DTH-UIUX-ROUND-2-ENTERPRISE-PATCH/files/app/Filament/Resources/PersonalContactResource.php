<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonalContactResource\Pages;
use App\Models\Crm\PersonalContactProfile;
use App\Models\Marketing\Contact;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PersonalContactResource extends Resource
{
    protected static ?string $model = PersonalContactProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
    }

    public static function getModelLabel(): string
    {
        return __('resource.personal_contact.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.personal_contact.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.contact_info'))
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('contact_id')
                        ->label(__('field.contact'))
                        ->options(fn (): array => Contact::query()
                            ->where('contact_type', 'personal')
                            ->with('personalProfile')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (Contact $contact): array => [
                                $contact->id => trim(($contact->full_name ?: __('resource.personal_contact.singular')).' · '.($contact->email ?: '#'.$contact->id)),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('first_name')->label(__('field.first_name'))->required()->maxLength(255),
                    TextInput::make('last_name')->label(__('field.last_name'))->required()->maxLength(255),
                    TextInput::make('email')->label(__('field.email'))->email()->maxLength(255),
                    TextInput::make('phone')->label(__('field.phone'))->tel()->maxLength(30),
                    DatePicker::make('date_of_birth')->label(__('field.date_of_birth'))->native(false),
                    Select::make('gender')
                        ->label(__('field.gender'))
                        ->options([
                            'male' => __('gender.male'),
                            'female' => __('gender.female'),
                            'other' => __('gender.other'),
                            'unspecified' => __('gender.unspecified'),
                        ])
                        ->native(false),
                    TextInput::make('province')->label(__('field.province'))->maxLength(100),
                    TextInput::make('occupation')->label(__('field.occupation'))->maxLength(255),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')->label(__('field.first_name'))->searchable()->sortable(),
                TextColumn::make('last_name')->label(__('field.last_name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('field.email'))->searchable(),
                TextColumn::make('phone')->label(__('field.phone'))->searchable(),
                TextColumn::make('province')->label(__('field.province'))->toggleable(),
                TextColumn::make('created_at')->label(__('field.created_at'))->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPersonalContacts::route('/'),
            'create' => Pages\CreatePersonalContact::route('/create'),
            'edit' => Pages\EditPersonalContact::route('/{record}/edit'),
        ];
    }
}
