<?php

namespace App\Filament\Resources;

use App\Enums\Crm\ContactType;
use App\Filament\Resources\ContactResource\Pages;
use App\Models\Marketing\Contact;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAnyMarketingUser() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('contact_type')->options([
                'personal' => __('enum.contact_type.personal'),
                'business' => __('enum.contact_type.business'),
            ])->required(),
            Select::make('owner_user_id')->relationship('owner', 'name')->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('contact_type')->badge()
                ->color(fn (?ContactType $state): string => match ($state?->value) {
                    'business' => 'warning',
                    'personal' => 'info',
                    default => 'gray',
                }),
            TextColumn::make('full_name')->label(__('field.name'))->searchable(),
            TextColumn::make('email')->label(__('field.email'))->searchable(),
            TextColumn::make('phone')->label(__('field.phone'))->searchable(),
            TextColumn::make('owner.name')->label(__('field.owner'))->toggleable(),
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
            ])->filters([
            SelectFilter::make('contact_type')->options([
                'personal' => __('enum.contact_type.personal'),
                'business' => __('enum.contact_type.business'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
        ];
    }
}
