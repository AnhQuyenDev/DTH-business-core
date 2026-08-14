<?php

namespace App\Filament\Resources\Sales\QuotationResource\RelationManagers;

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
        return __('field.payment');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_code')->label(__('field.payment_code')),
                TextColumn::make('paid_at')->label(__('field.payment_date'))->dateTime('d/m/Y H:i'),
                TextColumn::make('amount')->label(__('field.gross_collected'))->money('VND'),
                TextColumn::make('net_amount')->label(__('field.net_revenue'))->money('VND'),
                TextColumn::make('tax_amount')->label(__('field.vat'))->money('VND'),
                TextColumn::make('transfer_reference')->label(__('field.transfer_reference'))->placeholder('—'),
                TextColumn::make('status')->label(__('field.status'))->badge()->color(fn (?string $state): string => BadgePalette::status($state, category: 'finance.payment_record_status')),
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
            ]);
    }
}
