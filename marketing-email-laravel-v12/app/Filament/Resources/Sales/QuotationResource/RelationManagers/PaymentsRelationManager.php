<?php

namespace App\Filament\Resources\Sales\QuotationResource\RelationManagers;

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
        return 'Thanh toán';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_code')->label('Mã thanh toán'),
                TextColumn::make('paid_at')->label('Ngày thanh toán')->dateTime('d/m/Y H:i'),
                TextColumn::make('amount')->label('Thực thu')->money('VND'),
                TextColumn::make('net_amount')->label('Trước VAT')->money('VND'),
                TextColumn::make('tax_amount')->label('VAT')->money('VND'),
                TextColumn::make('transfer_reference')->label('Mã giao dịch')->placeholder('—'),
                TextColumn::make('status')->label('Trạng thái')->badge()->color('success'),
            ])
            ->actions([
                Action::make('receipt')
                    ->label('Biên lai')
                    ->icon('heroicon-o-document-check')
                    ->url(fn (Payment $record): ?string => $record->receipt
                        ? route('finance.payment-receipts', ['receipt' => $record->receipt])
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Payment $record): bool => $record->receipt !== null),
            ]);
    }
}
