<?php

namespace App\Filament\Resources\Sales\QuotationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EmailLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'emailLogs';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.email_logs');
    }

    public function getLabel(): string
    {
        return __('relation.title.email_logs');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sender_name')
                    ->label('Người gửi')
                    ->description(fn ($record): ?string => $record->sender_email)
                    ->placeholder('—'),
                TextColumn::make('sendingAccount.name')
                    ->label('Tài khoản gửi')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('recipient_email')
                    ->label(__('field.recipient_email')),
                TextColumn::make('subject')
                    ->label(__('field.subject'))
                    ->limit(50),
                TextColumn::make('status')
                    ->label(__('field.status'))
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => $state instanceof \BackedEnum
                            && method_exists($state, 'label')
                                ? $state->label()
                                : ($state ?? '')
                    )
                    ->color(
                        fn ($state): string => $state instanceof \BackedEnum
                            && method_exists($state, 'color')
                                ? $state->color()
                                : 'gray'
                    ),
                TextColumn::make('queued_at')
                    ->label(__('field.queued_at'))
                    ->dateTime('d/m/Y H:i:s'),
                TextColumn::make('sent_at')
                    ->label(__('field.sent_at'))
                    ->dateTime('d/m/Y H:i:s'),
                TextColumn::make('error_message')
                    ->label(__('field.error_message'))
                    ->wrap()
                    ->limit(120),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->headerActions([]);
    }
}
