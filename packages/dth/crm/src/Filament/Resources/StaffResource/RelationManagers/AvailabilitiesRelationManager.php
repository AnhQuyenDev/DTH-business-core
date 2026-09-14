<?php

namespace Dth\Crm\Filament\Resources\StaffResource\RelationManagers;

use Dth\Crm\Enums\StaffAvailabilityStatus;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\StatusColor;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AvailabilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilities';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return UiText::get('relations.staff_availability', 'Availability calendar');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->label(UiText::get('fields.date', 'Date'))
                ->required(),
            Select::make('status')
                ->label(UiText::get('common.fields.status', 'Status'))
                ->options(StaffAvailabilityStatus::options())
                ->required()
                ->native(false),
            Textarea::make('note')
                ->label(UiText::get('common.fields.notes', 'Notes')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label(UiText::get('fields.date', 'Date'))
                    ->date('d/m/Y'),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('availability_status', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'availability_status')),
                TextColumn::make('note')
                    ->label(UiText::get('common.fields.notes', 'Notes')),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label(UiText::get('actions.add_availability', 'Add availability'))
                    ->icon('heroicon-o-calendar-days'),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete')),
                ]),
            ]);
    }
}
