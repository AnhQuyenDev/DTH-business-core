<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\StaffResource\Pages;
use Dth\Crm\Filament\Resources\StaffResource\RelationManagers\AvailabilitiesRelationManager;
use Dth\Crm\Models\Staff;
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

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 70;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.staff', 'CRM staff', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.staff', 'CRM staff member', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.staff_plural', 'CRM staff', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.staff', 'CRM staff'))
                ->schema([
                    TextInput::make('staff_code')
                        ->label(UiText::get('fields.staff_code', 'Staff code')),
                    TextInput::make('name')
                        ->label(UiText::get('common.fields.name', 'Name'))
                        ->required(),
                    TextInput::make('email')
                        ->label(UiText::get('common.fields.email', 'Email'))
                        ->email(),
                    TextInput::make('phone')
                        ->label(UiText::get('fields.phone', 'Phone'))
                        ->tel(),
                    TextInput::make('department')
                        ->label(UiText::get('fields.department', 'Department')),
                    TextInput::make('position')
                        ->label(UiText::get('fields.position', 'Position')),
                    Select::make('employment_status')
                        ->label(UiText::get('common.fields.status', 'Status'))
                        ->options(CrmOptions::employmentStatuses())
                        ->native(false),
                    TextInput::make('lead_capacity')
                        ->label(UiText::get('fields.lead_capacity', 'Lead capacity'))
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('customer_capacity')
                        ->label(UiText::get('fields.customer_capacity', 'Customer capacity'))
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('distribution_weight')
                        ->label(UiText::get('fields.distribution_weight', 'Distribution weight'))
                        ->numeric()
                        ->minValue(0),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staff_code')
                    ->label(UiText::get('fields.staff_code', 'Staff code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(UiText::get('fields.staff_name', 'Staff member'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department')
                    ->label(UiText::get('fields.department', 'Department'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->label(UiText::get('fields.position', 'Position'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employment_status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('employment_status', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'employment_status')),
            ])
            ->filters([
                SelectFilter::make('employment_status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(CrmOptions::employmentStatuses()),
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
            'index' => Pages\ListStaffs::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'view' => Pages\ViewStaff::route('/{record}'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [AvailabilitiesRelationManager::class];
    }
}
