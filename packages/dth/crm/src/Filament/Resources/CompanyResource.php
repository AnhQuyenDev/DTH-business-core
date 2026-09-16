<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Enums\CompanyLifecycleStage;
use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\CompanyResource\Pages;
use Dth\Crm\Filament\Resources\CompanyResource\RelationManagers\AssignmentsRelationManager;
use Dth\Crm\Filament\Resources\CompanyResource\RelationManagers\ContactsRelationManager;
use Dth\Crm\Models\Company;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\StatusColor;
use Dth\Crm\Support\UiText;
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

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.companies', 'Companies', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.company', 'Company', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.companies', 'Companies', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.company', 'Company'))
                ->schema([
                    TextInput::make('company_code')
                        ->label(UiText::get('fields.company_code', 'Company code')),
                    TextInput::make('legal_name')
                        ->label(UiText::get('fields.legal_name', 'Legal name'))
                        ->required(),
                    TextInput::make('tax_code')
                        ->label(UiText::get('fields.tax_code', 'Tax code')),
                    TextInput::make('email_domain')
                        ->label(UiText::get('fields.email_domain', 'Email domain')),
                    TextInput::make('phone')
                        ->label(UiText::get('fields.phone', 'Phone'))
                        ->tel(),
                    TextInput::make('industry')
                        ->label(UiText::get('fields.industry', 'Industry')),
                    Textarea::make('address')
                        ->label(UiText::get('fields.address', 'Address'))
                        ->rows(3)
                        ->columnSpanFull(),
                    Select::make('lifecycle_stage')
                        ->label(UiText::get('fields.lifecycle_stage', 'Lifecycle stage'))
                        ->options(CompanyLifecycleStage::options())
                        ->native(false),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_code')
                    ->label(UiText::get('fields.company_code', 'Company code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('legal_name')
                    ->label(UiText::get('fields.company_name', 'Company name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tax_code')
                    ->label(UiText::get('fields.tax_code', 'Tax code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('industry')
                    ->label(UiText::get('fields.industry', 'Industry'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lifecycle_stage')
                    ->label(UiText::get('fields.lifecycle_stage', 'Lifecycle stage'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('company_lifecycle', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'company_lifecycle')),
            ])
            ->filters([
                SelectFilter::make('lifecycle_stage')
                    ->label(UiText::get('fields.lifecycle_stage', 'Lifecycle stage'))
                    ->options(CompanyLifecycleStage::options()),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->icon('heroicon-o-eye')
                        ->label(UiText::get('common.actions.view', 'View')),
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
            'index' => Pages\ListCompanys::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ContactsRelationManager::class,
            AssignmentsRelationManager::class,
        ];
    }
}
