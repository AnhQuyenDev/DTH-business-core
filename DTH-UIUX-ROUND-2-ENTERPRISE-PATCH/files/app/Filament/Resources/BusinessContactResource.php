<?php

namespace App\Filament\Resources;

use App\Enums\Crm\TaxVerificationStatus;
use App\Filament\Resources\BusinessContactResource\Pages;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Marketing\Contact;
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

class BusinessContactResource extends Resource
{
    protected static ?string $model = BusinessContactProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
    }

    public static function getModelLabel(): string
    {
        return __('resource.business_contact.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.business_contact.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.company_info'))
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('contact_id')
                        ->label(__('field.contact'))
                        ->options(fn (): array => Contact::query()
                            ->where('contact_type', 'business')
                            ->with('businessProfile')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (Contact $contact): array => [
                                $contact->id => trim(($contact->company_name ?: __('resource.business_contact.singular')).' · '.($contact->email ?: '#'.$contact->id)),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('company_name')->label(__('field.company_name'))->required()->maxLength(255),
                    TextInput::make('tax_code')->label(__('field.tax_code'))->maxLength(50),
                    TextInput::make('business_email')->label(__('field.business_email'))->email()->maxLength(255),
                    TextInput::make('business_phone')->label(__('field.business_phone'))->tel()->maxLength(30),
                    TextInput::make('legal_representative')->label(__('field.legal_representative'))->maxLength(255),
                    TextInput::make('industry')->label(__('field.industry'))->maxLength(255),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')->label(__('field.company_name'))->searchable()->sortable(),
                TextColumn::make('tax_code')->label(__('field.tax_code'))->searchable(),
                TextColumn::make('business_email')->label(__('field.business_email'))->searchable(),
                TextColumn::make('business_phone')->label(__('field.business_phone')),
                TextColumn::make('tax_verification_status')
                    ->label(__('field.tax_verification_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : (TaxVerificationStatus::tryFrom((string) $state)?->color() ?? 'gray')),
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
            'index' => Pages\ListBusinessContacts::route('/'),
            'create' => Pages\CreateBusinessContact::route('/create'),
            'edit' => Pages\EditBusinessContact::route('/{record}/edit'),
        ];
    }
}
