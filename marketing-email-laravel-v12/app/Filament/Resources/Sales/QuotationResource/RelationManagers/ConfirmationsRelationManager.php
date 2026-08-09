<?php

namespace App\Filament\Resources\Sales\QuotationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ConfirmationsRelationManager extends RelationManager
{
    protected static string $relationship = 'confirmations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
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
                TextColumn::make('confirmation_type')
                    ->label(__('field.confirmation_type'))
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => $state instanceof \BackedEnum
                            && method_exists($state, 'label')
                                ? $state->label()
                                : (string) $state
                    ),
                TextColumn::make('signer_name')
                    ->label(__('field.signer_name')),
                TextColumn::make('signer_email')
                    ->label(__('field.email')),
                TextColumn::make('verification_method')
                    ->label(__('sales.confirmation.verification_channel'))
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        return match (data_get(
                            $record->confirmation_data,
                            'verification_method'
                        )) {
                            'email_otp' => __('sales.confirmation.channel_email_otp'),
                            'phone_recorded_by_sales' => __('sales.confirmation.channel_phone_sales'),
                            default => __('common.not_available'),
                        };
                    }),
                TextColumn::make('recorded_by')
                    ->label(__('sales.confirmation.recorded_by'))
                    ->getStateUsing(
                        fn ($record): string => (string) (
                            data_get(
                                $record->confirmation_data,
                                'recorded_by_name'
                            ) ?: '—'
                        )
                    ),
                TextColumn::make('confirmed_at')
                    ->label(__('field.confirmed_at'))
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('ip_address')
                    ->label(__('field.ip_address'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([])
            ->headerActions([]);
    }
}
