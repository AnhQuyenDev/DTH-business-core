<?php

namespace App\Filament\Resources\Finance;

use App\Filament\Resources\Finance\PaymentResource\Pages;
use App\Models\Finance\Payment;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return __('finance.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('finance.payment_history');
    }

    public static function getModelLabel(): string
    {
        return 'Thanh toán';
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.payment_history');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user !== null && (
            $user->isAdmin()
            || $user->canReadAcrossBusiness()
            || $user->isFinanceStaff()
            || $user->isSalesManager()
            || $user->isMarketingManager()
        );
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_code')->label('Mã thanh toán')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('paid_at')->label('Ngày thanh toán')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('customer.display_name')->label('Khách hàng')->searchable()->placeholder('—'),
                TextColumn::make('quotation.quotation_code')->label('Báo giá')->searchable(),
                TextColumn::make('amount')->label('Thực thu')->money('VND')->sortable(),
                TextColumn::make('net_amount')->label('Doanh thu trước VAT')->money('VND')->sortable()->toggleable(),
                TextColumn::make('tax_amount')->label('VAT')->money('VND')->toggleable(),
                TextColumn::make('attribution.utm_source')
                    ->label('Nguồn UTM')
                    ->formatStateUsing(fn (?string $state, Payment $record): string => $state ?: ($record->attribution?->acquisition_source ?: '—'))
                    ->badge(),
                TextColumn::make('attribution.marketingCampaign.name')->label('Chiến dịch Marketing')->placeholder('—')->toggleable(),
                TextColumn::make('salesStaff.full_name')->label('Sales')->placeholder('—')->toggleable(),
                TextColumn::make('verifiedBy.name')->label('Finance xác minh')->placeholder('—')->toggleable(),
                TextColumn::make('status')->label('Trạng thái')->badge()->color(fn (string $state): string => $state === Payment::STATUS_VERIFIED ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    Payment::STATUS_VERIFIED => 'Đã xác minh',
                    Payment::STATUS_REFUNDED => 'Đã hoàn tiền',
                ]),
            ])
            ->actions([
                Action::make('receipt')
                    ->label('Xem biên lai')
                    ->icon('heroicon-o-document-check')
                    ->url(fn (Payment $record): ?string => $record->receipt
                        ? route('finance.payment-receipts', ['receipt' => $record->receipt])
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Payment $record): bool => $record->receipt !== null),
                Action::make('quotation')
                    ->label('Mở báo giá')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Payment $record): ?string => $record->quotation
                        ? \App\Filament\Resources\Sales\QuotationResource::getUrl('view', ['record' => $record->quotation])
                        : null),
            ])
            ->defaultSort('paid_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'customer',
            'quotation',
            'attribution.marketingCampaign',
            'salesStaff',
            'verifiedBy',
            'receipt',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
