<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

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
        return 'Lịch sử thanh toán';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_code')->label('Mã thanh toán'),
                TextColumn::make('quotation.quotation_code')->label('Báo giá'),
                TextColumn::make('paid_at')->label('Ngày thanh toán')->dateTime('d/m/Y H:i'),
                TextColumn::make('amount')->label('Thực thu')->money('VND'),
                TextColumn::make('net_amount')->label('Trước VAT')->money('VND'),
                TextColumn::make('attribution.utm_source')
                    ->label('Nguồn')
                    ->formatStateUsing(fn (?string $state, Payment $record): string => $state ?: ($record->attribution?->acquisition_source ?: '—')),
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
            ])
            ->defaultSort('paid_at', 'desc');
    }
}
