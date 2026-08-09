<?php

namespace App\Filament\Resources\Finance;

use App\Filament\Resources\Finance\PaymentResource\Pages;
use App\Models\Finance\Payment;
use App\Support\Ui\BadgePalette;
use App\Support\UtmOptions;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
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
        return __('navigation.group.finance');
    }

    public static function getNavigationLabel(): string
    {
        return __('finance.payment_history');
    }

    public static function getModelLabel(): string
    {
        return __('field.payment');
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
                TextColumn::make('payment_code')->label(__('field.payment_code'))->searchable()->sortable()->weight('semibold'),
                TextColumn::make('paid_at')->label(__('field.payment_date'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('customer.display_name')->label(__('resource.customer.singular'))->searchable()->placeholder('—'),
                TextColumn::make('quotation.quotation_code')->label(__('field.quotation'))->searchable(),
                TextColumn::make('amount')->label(__('field.gross_collected'))->money('VND')->sortable(),
                TextColumn::make('net_amount')->label(__('field.net_revenue'))->money('VND')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tax_amount')->label(__('field.vat'))->money('VND')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('attribution.utm_source')
                    ->label(__('field.utm_source_short'))
                    ->formatStateUsing(function (?string $state, Payment $record): string {
                        $source = $state ?: $record->attribution?->acquisition_source;

                        return $source
                            ? (UtmOptions::sources()[$source] ?? str($source)->headline()->toString())
                            : '—';
                    })
                    ->badge(),
                TextColumn::make('attribution.marketingCampaign.name')
                    ->label(__('field.marketing_campaign_short'))
                    ->limit(36)
                    ->tooltip(fn (Payment $record): ?string => $record->attribution?->marketingCampaign?->name)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('salesStaff.full_name')
                    ->label(__('field.sales_owner'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('verifiedBy.name')
                    ->label(__('field.finance_verified_by'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('field.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Payment::STATUS_VERIFIED => __('field.payment_status_verified'),
                        Payment::STATUS_REFUNDED => __('field.payment_status_refunded'),
                        default => str($state)->headline()->toString(),
                    })
                    ->color(fn (string $state): string => BadgePalette::status($state)),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    Payment::STATUS_VERIFIED => __('field.payment_status_verified'),
                    Payment::STATUS_REFUNDED => __('field.payment_status_refunded'),
                ]),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('receipt')
                        ->label(__('action.view_receipt'))
                        ->icon('heroicon-o-document-check')
                        ->url(fn (Payment $record): ?string => $record->receipt
                            ? route('finance.payment-receipts', ['receipt' => $record->receipt])
                            : null)
                        ->openUrlInNewTab()
                        ->visible(fn (Payment $record): bool => $record->receipt !== null),
                    Action::make('quotation')
                        ->label(__('action.open_quotation'))
                        ->icon('heroicon-o-document-text')
                        ->url(fn (Payment $record): ?string => $record->quotation
                            ? \App\Filament\Resources\Sales\QuotationResource::getUrl('view', ['record' => $record->quotation])
                            : null),
                ])
                    ->label(__('action.actions'))
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->iconButton(),
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
