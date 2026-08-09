<?php

namespace App\Filament\Resources\CustomerDistributionBatchResource\RelationManagers;

use App\Support\Ui\BadgePalette;
use App\Enums\Crm\CustomerAssignmentReason;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.distribution_items');
    }

    public function getLabel(): string
    {
        return __('relation.title.distribution_items');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('customer.display_name')
                    ->label(__('field.customer'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('originalOwner.full_name')
                    ->label(__('field.original_owner')),
                Tables\Columns\TextColumn::make('assignedStaff.full_name')
                    ->label(__('field.assigned_staff')),
                Tables\Columns\TextColumn::make('assignment_type')
                    ->label(__('field.assignment_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => match ($state instanceof \BackedEnum ? $state->value : $state) {
                        'owner' => 'success',
                        'support' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('result_status')
                    ->label(__('field.result_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __("field.{$state}"))
                    ->color(fn ($state): string => match ($state) {
                        'success' => 'success',
                        'skipped' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reason')
                    ->label(__('field.reason'))
                    ->formatStateUsing(fn (string $state): string => CustomerAssignmentReason::tryFrom($state)?->label() ?? $state),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
