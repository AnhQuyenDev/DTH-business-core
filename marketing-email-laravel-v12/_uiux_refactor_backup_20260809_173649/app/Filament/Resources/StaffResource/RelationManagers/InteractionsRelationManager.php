<?php

namespace App\Filament\Resources\StaffResource\RelationManagers;

use App\Enums\Crm\InteractionStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.interactions');
    }

    public function getLabel(): string
    {
        return __('relation.title.interactions');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.display_name')->label(__('field.customer'))->searchable(),
                TextColumn::make('interaction_type')->label(__('field.type'))->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'call', 'email', 'message', 'meeting' => 'info',
                        'support', 'follow_up' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')->label(__('field.status'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : (InteractionStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('subject')->label(__('field.subject'))->limit(40),
                TextColumn::make('interaction_at')->label(__('field.interaction_at'))->dateTime(),
            ])
            ->defaultSort('interaction_at', 'desc')
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
