<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Support\Ui\BadgePalette;
use App\Models\Finance\Payment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('finance.payment_history');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_code')->label(__('field.payment_code')),
                TextColumn::make('quotation.quotation_code')->label(__('field.quotation')),
                TextColumn::make('paid_at')->label(__('field.payment_date'))->dateTime('d/m/Y H:i'),
                TextColumn::make('amount')->label(__('field.gross_collected'))->money('VND'),
                TextColumn::make('net_amount')->label(__('field.net_revenue'))->money('VND'),
                TextColumn::make('attribution.utm_source')
                    ->label(__('field.source'))
                    ->formatStateUsing(fn (?string $state, Payment $record): string => $state ?: ($record->attribution?->acquisition_source ?: '—')),
                TextColumn::make('status')->label(__('field.status'))->badge()->color(fn (?string $state): string => BadgePalette::status($state)),
            ])
            ->actions([
                Action::make('receipt')
                    ->label(__('finance.receipt'))
                    ->icon('heroicon-o-document-check')
                    ->url(fn (Payment $record): ?string => $record->receipt
                        ? route('finance.payment-receipts', ['receipt' => $record->receipt])
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Payment $record): bool => $record->receipt !== null),
            ])
            ->defaultSort('paid_at', 'desc');
    }
}
