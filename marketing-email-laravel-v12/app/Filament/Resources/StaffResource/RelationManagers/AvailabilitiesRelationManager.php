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
        return __('configuration.staff.availability_title');
    }

    public function getLabel(): string
    {
        return __('configuration.staff.availability_title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('configuration.staff.availability_empty'))
            ->emptyStateDescription(__('configuration.staff.availability_empty_help'))
            ->columns([
                TextColumn::make('status')
                    ->label(__('configuration.staff.availability_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : (StaffAvailabilityStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('starts_at')->label(__('field.starts_at'))->dateTime('d/m/Y H:i'),
                TextColumn::make('ends_at')->label(__('field.ends_at'))->dateTime('d/m/Y H:i'),
                IconColumn::make('can_receive_new_customers')->label(__('field.can_receive_new_customers'))->boolean(),
                IconColumn::make('can_support_customers')->label(__('field.can_support_customers'))->boolean(),
                TextColumn::make('reason')->label(__('field.reason'))->limit(30),
            ])
            ->defaultSort('starts_at', 'desc')
            ->actions([
                ActionGroup::make([
                    EditAction::make()->label(__('configuration.common.edit')),
                    DeleteAction::make()->label(__('configuration.common.delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('configuration.staff.availability_create')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('configuration.common.delete_selected')),
                ]),
            ]);
    }
}
