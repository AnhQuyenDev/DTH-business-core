<?php

namespace App\Filament\Resources\StaffResource\RelationManagers;

use App\Enums\Crm\StaffAvailabilityStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AvailabilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilities';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.availabilities');
    }

    public function getLabel(): string
    {
        return __('relation.title.availabilities');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : (StaffAvailabilityStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('starts_at')->dateTime(),
                TextColumn::make('ends_at')->dateTime(),
                IconColumn::make('can_receive_new_customers')->boolean(),
                IconColumn::make('can_support_customers')->boolean(),
                TextColumn::make('reason')->limit(30),
            ])
            ->defaultSort('starts_at', 'desc')
            ->actions([ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->headerActions([
                CreateAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
