<?php

namespace App\Filament\Resources\Sales;

use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Filament\Resources\Sales\PaymentTrackingResource\Pages;
use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationPaymentService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Support\Collection;

class PaymentTrackingResource extends Resource
{
    protected static ?string $model = Quotation::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getModelLabel(): string
    {
        return __('resource.payment_tracking.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.payment_tracking.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isCustomerServiceManager();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_code')->label(__('field.quotation_code'))->searchable()->sortable(),
                TextColumn::make('customer.display_name')->label(__('field.customer'))->searchable(),
                TextColumn::make('grand_total')->label(__('field.grand_total'))->money('VND')->sortable(),
                TextColumn::make('payment_status')->label(__('field.payment_status'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
                TextColumn::make('status')->label(__('field.quotation_status'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
                TextColumn::make('accepted_at')->label(__('field.accepted_at'))->dateTime()->sortable(),
                TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label(__('field.payment_status'))
                    ->options(PaymentStatus::options()),
                SelectFilter::make('status')
                    ->label(__('field.quotation_status'))
                    ->options([
                        QuotationStatus::Accepted->value => QuotationStatus::Accepted->label(),
                        QuotationStatus::Sent->value => QuotationStatus::Sent->label(),
                        QuotationStatus::Viewed->value => QuotationStatus::Viewed->label(),
                    ]),
            ])
            ->actions([ActionGroup::make([
                Action::make('mark_paid')
                    ->label(__('action.mark_paid'))
                    ->icon('heroicon-o-check-circle')->color('success')
                    ->action(function (Quotation $q) {
                        app(QuotationPaymentService::class)->updateStatus(
                            $q, PaymentStatus::Paid, auth()->user(),
                            __('note.payment_tracking_update')
                        );
                        Notification::make()->title(__('notification.payment_updated'))->success()->send();
                    })
                    ->visible(fn (Quotation $q): bool => in_array($q->payment_status?->value, ['unpaid', 'pending_verification', 'partially_paid'])),

                Action::make('mark_pending')
                    ->label(__('action.mark_pending'))
                    ->icon('heroicon-o-clock')->color('warning')
                    ->action(function (Quotation $q) {
                        app(QuotationPaymentService::class)->updateStatus(
                            $q, PaymentStatus::PendingVerification, auth()->user(),
                            __('note.mark_pending_note')
                        );
                        Notification::make()->title(__('notification.updated'))->warning()->send();
                    })
                    ->visible(fn (Quotation $q): bool => $q->payment_status?->value === 'unpaid'),

                Action::make('mark_unpaid')
                    ->label(__('action.mark_unpaid'))
                    ->color('danger')
                    ->action(function (Quotation $q) {
                        app(QuotationPaymentService::class)->updateStatus(
                            $q, PaymentStatus::Unpaid, auth()->user(),
                            __('note.mark_unpaid_note')
                        );
                        Notification::make()->title(__('notification.updated'))->send();
                    })
                    ->visible(fn (Quotation $q): bool => $q->payment_status?->value === 'pending_verification'),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_mark_paid')
                        ->label(__('action.bulk_mark_paid'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('action.bulk_mark_paid'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                if (in_array($record->payment_status?->value, ['unpaid', 'pending_verification', 'partially_paid'])) {
                                    app(QuotationPaymentService::class)->updateStatus(
                                        $record, PaymentStatus::Paid, auth()->user(),
                                        __('note.payment_tracking_update')
                                    );
                                }
                            }
                            Notification::make()->title(__('notification.payment_updated'))->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentTrackings::route('/'),
        ];
    }
}
