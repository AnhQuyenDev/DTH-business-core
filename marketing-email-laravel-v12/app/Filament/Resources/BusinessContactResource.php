<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessContactResource\Pages;
use App\Enums\Crm\TaxVerificationStatus;
use App\Models\Crm\BusinessContactProfile;
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

class BusinessContactResource extends Resource
{
    protected static ?string $model = BusinessContactProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('contact_id')->relationship('contact', 'id')->searchable()->required(),
            TextInput::make('company_name')->required()->maxLength(255),
            TextInput::make('tax_code')->maxLength(50),
            TextInput::make('business_email')->email()->maxLength(255),
            TextInput::make('business_phone')->maxLength(30),
            TextInput::make('legal_representative')->maxLength(255),
            TextInput::make('industry')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('company_name')->searchable()->sortable(),
            TextColumn::make('tax_code')->searchable(),
            TextColumn::make('business_email')->searchable(),
            TextColumn::make('business_phone'),
            TextColumn::make('tax_verification_status')->badge()
                ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : (TaxVerificationStatus::tryFrom((string) $state)?->color() ?? 'gray')),
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
            'index' => Pages\ListBusinessContacts::route('/'),
            'create' => Pages\CreateBusinessContact::route('/create'),
            'edit' => Pages\EditBusinessContact::route('/{record}/edit'),
        ];
    }
}
