<?php

namespace App\Filament\Resources\Sales\QuotationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConfirmationsRelationManager extends RelationManager
{
    protected static string $relationship = 'confirmations';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.confirmations');
    }

    public function getLabel(): string
    {
        return __('relation.title.confirmations');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('confirmation_type')->label(__('field.confirmation_type')),
                TextColumn::make('signer_name')->label(__('field.signer_name')),
                TextColumn::make('signer_email')->label(__('field.email')),
                TextColumn::make('confirmed_at')->label(__('field.confirmed_at'))->dateTime(),
                TextColumn::make('ip_address')->label(__('field.ip_address')),
            ])
            ->actions([])
            ->headerActions([]);
    }
}
