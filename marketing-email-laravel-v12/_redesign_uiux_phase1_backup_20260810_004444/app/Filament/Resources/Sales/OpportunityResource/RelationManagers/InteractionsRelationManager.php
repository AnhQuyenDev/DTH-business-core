<?php

namespace App\Filament\Resources\Sales\OpportunityResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    public static function getTitle(
        Model $ownerRecord,
        string $pageClass
    ): string {
        return __('relation.title.opportunity_interactions');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('interaction_at')
                    ->label(__('field.interaction_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('interaction_type')
                    ->label(__('field.interaction_type'))
                    ->badge(),
                TextColumn::make('subject')
                    ->label(__('field.subject'))
                    ->limit(40),
                TextColumn::make('outcome')
                    ->label(__('field.outcome'))
                    ->toggleable(),
                TextColumn::make('staff.full_name')
                    ->label(__('field.staff'))
                    ->toggleable(),
                TextColumn::make('next_follow_up_at')
                    ->label(__('field.next_follow_up'))
                    ->dateTime('d/m/Y H:i')
                    ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'gray'
                    ),
            ])
            ->defaultSort('interaction_at', 'desc');
    }
}
