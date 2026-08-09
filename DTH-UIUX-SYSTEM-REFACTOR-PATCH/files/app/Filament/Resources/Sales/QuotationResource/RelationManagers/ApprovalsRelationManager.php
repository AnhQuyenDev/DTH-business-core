<?php

namespace App\Filament\Resources\Sales\QuotationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.approvals');
    }

    public function getLabel(): string
    {
        return __('relation.title.approvals');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('step')->label(__('field.step')),
                TextColumn::make('status')->label(__('field.status'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
                TextColumn::make('reason')->label(__('field.reason'))->limit(50),
                TextColumn::make('requested_at')->label(__('field.requested_at'))->dateTime('d/m/Y H:i'),
                TextColumn::make('reviewed_at')->label(__('field.reviewed_at'))->dateTime('d/m/Y H:i'),
            ])
            ->actions([])
            ->headerActions([]);
    }
}
