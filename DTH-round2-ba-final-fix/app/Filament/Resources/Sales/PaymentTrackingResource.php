<?php

namespace App\Filament\Resources\Sales;

use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Filament\Resources\Sales\PaymentTrackingResource\Pages;
use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationPaymentService;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
        $user = auth()->user();

        return $user !== null
            && (
                $user->isAdmin()
                || $user->canReadAcrossBusiness()
                || $user->isSalesManager()
                || $user->isFinanceStaff()
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_code')
                    ->label(__('field.quotation_code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('party_display_name')
                    ->label(__('field.customer'))
                    ->getStateUsing(fn (Quotation $record): string => $record->party_display_name)
                    ->description(fn (Quotation $record): ?string => $record->party_email),
                TextColumn::make('grand_total')
                    ->label(__('field.grand_total'))
                    ->money('VND')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label(__('field.payment_status'))
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label')
                            ? $state->label()
                            : ($state ?? '')
                    )
                    ->color(
                        fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color')
                            ? $state->color()
                            : 'gray'
                    ),
                TextColumn::make('latest_payment_notice')
                    ->label('Khách thông báo')
                    ->getStateUsing(function (Quotation $record): string {
                        $notice = $record->paymentNotices()
                            ->latest('id')
                            ->first();

                        if ($notice === null) {
                            return '—';
                        }

                        return number_format((float) $notice->declared_amount, 0, ',', '.').' VND';
                    })
                    ->description(function (Quotation $record): ?string {
                        $notice = $record->paymentNotices()->latest('id')->first();
                        if ($notice === null) {
                            return null;
                        }

                        return collect([
                            $notice->payer_name,
                            $notice->transfer_reference,
                        ])->filter()->implode(' • ');
                    }),
                TextColumn::make('status')
                    ->label(__('field.quotation_status'))
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label')
                            ? $state->label()
                            : ($state ?? '')
                    )
                    ->color(
                        fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color')
                            ? $state->color()
                            : 'gray'
                    ),
                TextColumn::make('accepted_at')
                    ->label(__('field.accepted_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label(__('field.payment_status'))
                    ->options(PaymentStatus::options()),
                SelectFilter::make('status')
                    ->label(__('field.quotation_status'))
                    ->options([
                        QuotationStatus::Accepted->value => QuotationStatus::Accepted->label(),
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('mark_paid')
                        ->label(__('action.mark_paid'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('note')
                                ->label('Ghi chú đối soát')
                                ->required()
                                ->rows(3)
                                ->maxLength(2000),
                        ])
                        ->action(function (Quotation $q, array $data): void {
                            app(QuotationPaymentService::class)->updateStatus(
                                $q,
                                PaymentStatus::Paid,
                                auth()->user(),
                                $data['note'],
                            );

                            Notification::make()
                                ->title(__('notification.payment_updated'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Quotation $q): bool =>
                            $q->status === QuotationStatus::Accepted
                            && (auth()->user()?->can('verifyPayment', $q) ?? false)
                            && in_array(
                                $q->payment_status,
                                [
                                    PaymentStatus::Unpaid,
                                    PaymentStatus::PendingVerification,
                                    PaymentStatus::PartiallyPaid,
                                ],
                                true,
                            )
                        ),

                    Action::make('mark_unpaid')
                        ->label('Đối soát không khớp')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('note')
                                ->label('Lý do không khớp')
                                ->required()
                                ->rows(3)
                                ->maxLength(2000),
                        ])
                        ->action(function (Quotation $q, array $data): void {
                            app(QuotationPaymentService::class)->updateStatus(
                                $q,
                                PaymentStatus::Unpaid,
                                auth()->user(),
                                $data['note'],
                            );

                            Notification::make()
                                ->title(__('notification.updated'))
                                ->warning()
                                ->send();
                        })
                        ->visible(fn (Quotation $q): bool =>
                            $q->status === QuotationStatus::Accepted
                            && $q->payment_status === PaymentStatus::PendingVerification
                            && (auth()->user()?->can('verifyPayment', $q) ?? false)
                        ),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        // Payment tracking starts only after the customer has accepted the
        // quotation. Earlier quotation workflow belongs to Sales, not Finance.
        return parent::getEloquentQuery()->where(
            'status',
            QuotationStatus::Accepted->value,
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentTrackings::route('/'),
        ];
    }
}
