<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonalContactResource\Pages;
use App\Models\Crm\PersonalContactProfile;
use Filament\Forms\Components\DatePicker;
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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('contact_id')->relationship('contact', 'id')->searchable()->required(),
            TextInput::make('first_name')->required()->maxLength(255),
            TextInput::make('last_name')->required()->maxLength(255),
            TextInput::make('email')->email()->maxLength(255),
            TextInput::make('phone')->maxLength(30),
            DatePicker::make('date_of_birth'),
            TextInput::make('gender')->maxLength(20),
            TextInput::make('province')->maxLength(100),
            TextInput::make('occupation')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('first_name')->searchable()->sortable(),
            TextColumn::make('last_name')->searchable()->sortable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('phone')->searchable(),
            TextColumn::make('province')->toggleable(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])
            ->actions([ActionGroup::make([
                ViewAction::make(),
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
            'index' => Pages\ListPersonalContacts::route('/'),
            'create' => Pages\CreatePersonalContact::route('/create'),
            'edit' => Pages\EditPersonalContact::route('/{record}/edit'),
        ];
    }
}
