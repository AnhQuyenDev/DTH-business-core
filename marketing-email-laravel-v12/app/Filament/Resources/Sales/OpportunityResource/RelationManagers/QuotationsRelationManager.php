<?php

namespace App\Filament\Resources\Sales\OpportunityResource\RelationManagers;

use App\Filament\Resources\Sales\QuotationResource;
use App\Models\Sales\Quotation;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class QuotationsRelationManager extends RelationManager
{
    protected static string $relationship = 'quotations';

    public static function getTitle(
        Model $ownerRecord,
        string $pageClass,
    ): string {
        return __('resource.quotation.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('quotation_code')
            ->columns([
                TextColumn::make('quotation_code')
                    ->label(__('field.quotation_code'))
                    ->searchable(),
                TextColumn::make('version')
                    ->label(__('field.version')),
                TextColumn::make('status')
                    ->label(__('field.status'))
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => $state->label()
                    )
                    ->color(
                        fn ($state): string => $state->color()
                    ),
                TextColumn::make('grand_total')
                    ->label(__('field.grand_total'))
                    ->money('VND'),
                TextColumn::make('valid_until')
                    ->label(__('field.valid_until'))
                    ->date('d/m/Y'),
                TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('action.view'))
                    ->icon('heroicon-o-eye')
                    ->url(
                        fn (Quotation $record): string => QuotationResource::getUrl('view', [
                            'record' => $record,
                        ])
                    ),
            ]);
    }
}
